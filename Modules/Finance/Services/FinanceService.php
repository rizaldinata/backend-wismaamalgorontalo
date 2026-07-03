<?php

namespace Modules\Finance\Services;

use App\Contracts\ConfigProviderInterface;
use App\Events\Finance\PembayaranDibatalkan;
use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Transaction;
use Modules\Finance\Contracts\PaymentStrategyInterface;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Events\PaymentSettled;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\RefundRequest;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Repositories\Contracts\RefundRequestRepositoryInterface;
use Modules\Finance\Strategies\ManualPaymentStrategy;
use Modules\Finance\Strategies\MidtransPaymentStrategy;
use Modules\Schedule\Enums\SchedulePaymentScheme;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Modules\Schedule\Services\ScheduleService;

class FinanceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly RefundRequestRepositoryInterface $refundRequestRepository,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
        private readonly ExpenseService $expenseService,
        private readonly ScheduleService $scheduleService,
        private readonly ImageService $imageService,
        private readonly ConfigProviderInterface $settingService,
        private readonly ManualPaymentStrategy $manualStrategy,
        private readonly MidtransPaymentStrategy $midtransStrategy,
    ) {}

    public function processPayment(int $invoiceId, array $data): Payment
    {
        return DB::transaction(function () use ($invoiceId, $data) {
            $invoice = $this->invoiceRepository->findById($invoiceId);

            if ($invoice->status === InvoiceStatus::PAID) {
                throw new \DomainException('Tagihan ini sudah lunas.');
            }

            if ($invoice->payment_expires_at && $invoice->payment_expires_at->isPast()) {
                throw new \DomainException('Batas waktu pembayaran telah habis. Silakan buat tagihan baru.');
            }

            $strategy = $this->resolveStrategy($data['payment_method']);

            return $strategy->process($invoice, $data);
        });
    }

    public function verifyPayment(int $paymentId, bool $isApproved, ?string $adminNotes = null): Payment
    {
        return DB::transaction(function () use ($paymentId, $isApproved, $adminNotes) {
            $payment = $this->paymentRepository->findOrFail($paymentId);

            if (in_array($payment->status, [PaymentStatus::VERIFIED, PaymentStatus::REJECTED, PaymentStatus::PAID])) {
                throw new \DomainException('Pembayaran ini sudah terproses dan tidak bisa diverifikasi ulang.');
            }

            $this->paymentRepository->update($payment, [
                'status' => $isApproved ? PaymentStatus::VERIFIED : PaymentStatus::REJECTED,
                'admin_notes' => $adminNotes,
            ]);

            if ($isApproved) {
                $this->invoiceRepository->updateStatus($payment->invoice, InvoiceStatus::PAID->value);

                $invoice = $payment->invoice;

                event(new PembayaranDiverifikasi(
                    paymentId: $payment->id,
                    invoiceId: $invoice->id,
                    scheduleId: $invoice->schedule_id ?? 0,
                    amount: (float) $invoice->amount,
                    tenantName: $invoice->tenant_name ?? '',
                    tenantPhone: $invoice->tenant_phone ?? '',
                    invoiceNumber: $invoice->invoice_number,
                    roomTitle: $invoice->room_number ?? '',
                    roomNumber: $invoice->room_number ?? '',
                    startDate: $invoice->period_start?->toDateString() ?? '',
                    endDate: $invoice->period_end?->toDateString() ?? '',
                    invoiceType: $invoice->type?->value ?? 'sewa',
                ));

                event(new PaymentSettled($payment));
            } else {
                $rejectedInvoice = $payment->invoice;
                event(new PembayaranDibatalkan(
                    paymentId: $payment->id,
                    invoiceId: $rejectedInvoice->id,
                    scheduleId: $rejectedInvoice->schedule_id ?? 0,
                    tenantName: $rejectedInvoice->tenant_name ?? '',
                    tenantPhone: $rejectedInvoice->tenant_phone ?? '',
                    amount: (float) $rejectedInvoice->amount,
                    paymentStatus: PaymentStatus::REJECTED->value,
                    invoiceType: $rejectedInvoice->type?->value ?? 'sewa',
                ));
            }

            return $payment;
        });
    }

    public function refundPayment(int $paymentId, string $reason): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason) {
            $payment = $this->paymentRepository->findOrFail($paymentId);

            if ($payment->status !== PaymentStatus::PAID || $payment->payment_method !== 'midtrans') {
                throw new \DomainException('Hanya metode Midtrans berstatus lunas yang dapat dikembalikan secara otomatis.');
            }

            try {
                Config::$serverKey = config('finance.midtrans.server_key');
                Config::$isProduction = config('finance.midtrans.is_production', false);

                $params = [
                    'refund_key' => 'refund-'.time().'-'.$paymentId,
                    'amount' => (int) $payment->invoice->amount,
                    'reason' => $reason,
                ];

                Transaction::refund($payment->transaction_id, $params);

                $this->paymentRepository->update($payment, [
                    'status' => PaymentStatus::REFUNDED->value,
                    'admin_notes' => 'Refunded: '.$reason,
                ]);

                $refundedInvoice = $payment->invoice;
                $this->invoiceRepository->updateStatus($refundedInvoice, InvoiceStatus::UNPAID->value);
                event(new PembayaranDibatalkan(
                    paymentId: $payment->id,
                    invoiceId: $refundedInvoice->id,
                    scheduleId: $refundedInvoice->schedule_id ?? 0,
                    tenantName: $refundedInvoice->tenant_name ?? '',
                    tenantPhone: $refundedInvoice->tenant_phone ?? '',
                    amount: (float) $refundedInvoice->amount,
                    paymentStatus: PaymentStatus::REFUNDED->value,
                    invoiceType: $refundedInvoice->type?->value ?? 'sewa',
                ));

                return $payment;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Refund Error: '.$e->getMessage());
                throw new \DomainException('Gagal memproses refund ke Midtrans. Saldo mungkin tidak mencukupi atau transaksi belum di-Settle.');
            }
        });
    }

    private function resolveStrategy(string $method): PaymentStrategyInterface
    {
        if ($method === 'midtrans' && ! config('finance.midtrans.enabled', true)) {
            throw new \DomainException('Penyedia layanan (Admin) sedang menonaktifkan fitur pembayaran dengan Midtrans saat ini.');
        }

        return match ($method) {
            'manual' => $this->manualStrategy,
            'midtrans' => $this->midtransStrategy,
            default => throw new InvalidArgumentException('Metode tidak didukung'),
        };
    }

    public function handleMidtransNotification(array $payload)
    {
        $orderId = $payload['order_id'];
        $payment = $this->paymentRepository->findByReference($orderId);
        if (! $payment) {
            return;
        }

        $transactionStatus = $payload['transaction_status'];
        $invoice = $payment->invoice;

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $this->paymentRepository->update($payment, ['status' => PaymentStatus::PAID->value]);
            $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::PAID->value);

            $invoiceType = $invoice->type?->value ?? 'sewa';

            event(new PembayaranDiterima(
                paymentId: $payment->id,
                invoiceId: $invoice->id,
                scheduleId: $invoice->schedule_id ?? 0,
                amount: (float) $invoice->amount,
                tenantName: $invoice->tenant_name ?? '',
                tenantPhone: $invoice->tenant_phone ?? '',
                invoiceType: $invoiceType,
                periodStart: $invoice->period_start?->toDateString(),
                periodEnd: $invoice->period_end?->toDateString(),
                roomNumber: $invoice->room_number,
            ));

            event(new PembayaranDiverifikasi(
                paymentId: $payment->id,
                invoiceId: $invoice->id,
                scheduleId: $invoice->schedule_id ?? 0,
                amount: (float) $invoice->amount,
                tenantName: $invoice->tenant_name ?? '',
                tenantPhone: $invoice->tenant_phone ?? '',
                invoiceNumber: $invoice->invoice_number ?? '',
                roomTitle: $invoice->room_number ?? '',
                roomNumber: $invoice->room_number ?? '',
                startDate: $invoice->period_start?->toDateString() ?? '',
                endDate: $invoice->period_end?->toDateString() ?? '',
                invoiceType: $invoiceType,
            ));

            event(new PaymentSettled($payment));
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $this->paymentRepository->update($payment, ['status' => PaymentStatus::FAILED->value]);
            $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::UNPAID->value);
            event(new PembayaranDibatalkan(
                paymentId: $payment->id,
                invoiceId: $invoice->id,
                scheduleId: $invoice->schedule_id ?? $invoice->lease_id ?? 0,
                tenantName: $invoice->tenant_name ?? '',
                tenantPhone: $invoice->tenant_phone ?? '',
                amount: (float) $invoice->amount,
                paymentStatus: PaymentStatus::FAILED->value,
                invoiceType: $invoice->type?->value ?? 'sewa',
            ));
        }
    }

    public function ajukanPembatalanDp(int $scheduleId, int $userId, array $bankData): RefundRequest
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);

        if ($schedule->tenant_user_id !== $userId) {
            throw new \DomainException('Anda tidak memiliki akses ke jadwal ini.');
        }

        $allowedStatuses = [ScheduleStatus::DP_TERBAYAR, ScheduleStatus::TERKONFIRMASI];
        if (! in_array($schedule->status, $allowedStatuses)) {
            throw new \DomainException('Pembatalan hanya bisa dilakukan untuk jadwal dengan status DP Terbayar atau Terkonfirmasi.');
        }

        if ($schedule->payment_scheme !== SchedulePaymentScheme::DP) {
            throw new \DomainException('Pembatalan dengan pengembalian dana hanya berlaku untuk skema pembayaran DP.');
        }

        if ($this->refundRequestRepository->hasPendingBySchedule($scheduleId)) {
            throw new \DomainException('Sudah ada permintaan pembatalan yang sedang menunggu diproses oleh admin.');
        }

        // Cari invoice DP yang sudah dibayar
        $invoice = \Modules\Finance\Models\Invoice::where('schedule_id', $schedule->id)
            ->where('status', InvoiceStatus::PAID->value)
            ->orderBy('created_at')
            ->first();

        if (! $invoice) {
            throw new \DomainException('Tidak ditemukan pembayaran yang sudah lunas untuk jadwal ini.');
        }

        $payment = $invoice->payments()
            ->where('status', PaymentStatus::PAID->value)
            ->latest()
            ->first();

        if (! $payment) {
            throw new \DomainException('Tidak ditemukan data pembayaran untuk tagihan ini.');
        }

        $isEligible = \Carbon\Carbon::now()->lt(
            \Carbon\Carbon::parse($schedule->start_date)->subDays(3)
        );

        return $this->refundRequestRepository->create([
            'schedule_id' => $scheduleId,
            'payment_id' => $payment->id,
            'bank_name' => $bankData['bank_name'],
            'account_number' => $bankData['account_number'],
            'account_holder_name' => $bankData['account_holder_name'],
            'refund_amount' => $invoice->amount,
            'is_refund_eligible' => $isEligible,
            'status' => 'pending',
        ]);
    }

    public function approveRefundRequest(int $id, ?UploadedFile $proof, float $adminFee, string $notes = ''): RefundRequest
    {
        return DB::transaction(function () use ($id, $proof, $adminFee, $notes) {
            $refundRequest = $this->refundRequestRepository->findOrFail($id);

            if ($refundRequest->status !== 'pending') {
                throw new \DomainException('Permintaan refund ini sudah diproses.');
            }

            $updateData = [
                'status' => 'processed',
                'admin_notes' => $notes,
                'processed_at' => now(),
            ];

            if ($refundRequest->is_refund_eligible) {
                if (! $proof) {
                    throw new \DomainException('Bukti transfer wajib diunggah untuk refund yang berhak.');
                }

                $proofPath = $this->imageService->uploadAndCompress($proof, 'refund-proofs');
                $updateData['proof_path'] = $proofPath;
                $updateData['admin_fee'] = $adminFee;

                $payment = $refundRequest->payment;
                $invoice = $payment->invoice;

                $this->paymentRepository->update($payment, [
                    'status' => PaymentStatus::REFUNDED->value,
                    'admin_notes' => 'Refund manual diproses: '.$notes,
                ]);

                $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::UNPAID->value);

                $this->expenseService->recordExpense([
                    'title' => 'Pengembalian Dana - '.$invoice->invoice_number,
                    'description' => 'Refund DP untuk '.$invoice->tenant_name.($notes ? ': '.$notes : ''),
                    'amount' => (float) $refundRequest->refund_amount,
                    'expense_date' => now(),
                    'reference_id' => $refundRequest->id,
                    'reference_type' => RefundRequest::class,
                ]);

                if ($adminFee > 0) {
                    $this->expenseService->recordExpense([
                        'title' => 'Biaya Admin Transfer Refund - '.$invoice->invoice_number,
                        'description' => 'Biaya transfer pengembalian dana untuk '.$invoice->tenant_name,
                        'amount' => $adminFee,
                        'expense_date' => now(),
                        'reference_id' => $refundRequest->id,
                        'reference_type' => RefundRequest::class.'_fee',
                    ]);
                }

                event(new PembayaranDibatalkan(
                    paymentId: $payment->id,
                    invoiceId: $invoice->id,
                    scheduleId: $invoice->schedule_id ?? 0,
                    tenantName: $invoice->tenant_name ?? '',
                    tenantPhone: $invoice->tenant_phone ?? '',
                    amount: (float) $refundRequest->refund_amount,
                    paymentStatus: PaymentStatus::REFUNDED->value,
                    invoiceType: $invoice->type?->value ?? 'sewa',
                ));
            }

            $this->scheduleService->batalkanJadwal($refundRequest->schedule_id);

            return $this->refundRequestRepository->update($refundRequest, $updateData);
        });
    }

    public function rejectRefundRequest(int $id, string $notes): RefundRequest
    {
        $refundRequest = $this->refundRequestRepository->findOrFail($id);

        if ($refundRequest->status !== 'pending') {
            throw new \DomainException('Permintaan refund ini sudah diproses.');
        }

        return $this->refundRequestRepository->update($refundRequest, [
            'status' => 'rejected',
            'admin_notes' => $notes,
        ]);
    }

    public static function getRecentActivities(int $limit = 5): array
    {
        $invoices = \Modules\Finance\Models\Invoice::latest()->limit($limit)->get();

        $scheduleIds = $invoices->pluck('schedule_id')->filter()->unique()->toArray();
        $scheduleData = [];

        if (! empty($scheduleIds) && \App\Support\ModuleGate::isActive('Schedule')) {
            $scheduleData = \Modules\Schedule\Services\ScheduleService::getByIds($scheduleIds);
        }

        return $invoices->map(function ($invoice) use ($scheduleData) {
            $arr = $invoice->toArray();
            $arr['schedule_data'] = $scheduleData[$invoice->schedule_id] ?? null;

            return $arr;
        })->toArray();
    }

    public static function getRecentBills(int $userId, int $limit = 5): array
    {
        $invoices = \Modules\Finance\Models\Invoice::where('tenant_user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();

        $scheduleIds = $invoices->pluck('schedule_id')->filter()->unique()->toArray();
        $scheduleData = [];

        if (! empty($scheduleIds) && \App\Support\ModuleGate::isActive('Schedule')) {
            $scheduleData = \Modules\Schedule\Services\ScheduleService::getByIds($scheduleIds);
        }

        return $invoices->map(function ($invoice) use ($scheduleData) {
            $arr = $invoice->toArray();
            $arr['schedule_data'] = $scheduleData[$invoice->schedule_id] ?? null;

            return $arr;
        })->toArray();
    }

    /**
     * Get payment status for multiple schedules
     * Returns an array mapping schedule_id to 'Lunas' or 'Belum Lunas'
     *
     * @param array $scheduleIds
     * @return array
     */
    public static function getPaymentStatusByScheduleIds(array $scheduleIds): array
    {
        $invoices = \Modules\Finance\Models\Invoice::whereIn('schedule_id', $scheduleIds)->get();
        
        $statusMap = [];
        foreach ($scheduleIds as $id) {
            $scheduleInvoices = $invoices->where('schedule_id', $id);
            $hasUnpaid = $scheduleInvoices->where('status', \Modules\Finance\Enums\InvoiceStatus::UNPAID)->count() > 0;
            $hasInvoices = $scheduleInvoices->count() > 0;
            $statusMap[$id] = ($hasInvoices && ! $hasUnpaid) ? 'Lunas' : 'Belum Lunas';
        }
        
        return $statusMap;
    }

    /**
     * Get total monthly income for the current month
     */
    public static function getMonthlyIncome(): float
    {
        return (float) \Modules\Finance\Models\Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.status', \Modules\Finance\Enums\PaymentStatus::VERIFIED)
            ->whereMonth('payments.created_at', now()->month)
            ->whereYear('payments.created_at', now()->year)
            ->sum('invoices.amount');
    }
}
