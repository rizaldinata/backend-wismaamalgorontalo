<?php

namespace Modules\Finance\Services;

use App\Events\Finance\PembayaranDibatalkan;
use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Transaction;
use Modules\Finance\Contracts\PaymentStrategyInterface;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Events\PaymentSettled;
use Modules\Finance\Models\Payment;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Strategies\ManualPaymentStrategy;
use Modules\Finance\Strategies\MidtransPaymentStrategy;
use App\Contracts\ConfigProviderInterface;

class FinanceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
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
                $invoice->load('schedule.room');
                $room = $invoice->schedule?->room;

                event(new PembayaranDiverifikasi(
                    paymentId: $payment->id,
                    invoiceId: $invoice->id,
                    scheduleId: $invoice->schedule_id ?? 0,
                    amount: (float) $invoice->amount,
                    tenantName: $invoice->tenant_name ?? '',
                    tenantPhone: $invoice->tenant_phone ?? '',
                    invoiceNumber: $invoice->invoice_number,
                    roomTitle: $room?->title ?? $room?->number ?? '',
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

            $invoice->load('schedule.room');
            $room = $invoice->schedule?->room;

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
                roomTitle: $room?->title ?? $room?->number ?? '',
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
}
