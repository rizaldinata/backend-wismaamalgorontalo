<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Contracts\PaymentStrategyInterface;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Events\PaymentSettled;
use Midtrans\Config;
use Midtrans\Transaction;
use Modules\Finance\Models\Payment; 
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Strategies\ManualPaymentStrategy;
use Modules\Finance\Strategies\MidtransPaymentStrategy;
use Modules\Rental\Services\RentalService;
use Modules\Setting\Services\SettingService;

class FinanceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly RentalService $rentalService,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly SettingService $settingService
    ) {}

    public function processPayment(int $invoiceId, array $data): Payment
    {
        return DB::transaction(function () use ($invoiceId, $data) {
            $invoice = $this->invoiceRepository->findById($invoiceId);

            if ($invoice->status === InvoiceStatus::PAID) {
                throw new \DomainException('Tagihan ini sudah lunas.');
            }

            $strategy = $this->resolveStrategy($data['payment_method']);
            return $strategy->process($invoice, $data);
        });
    }

    public function generateInvoiceForLease(int $leaseId, float $amount, Carbon $dueDate)
    {
        $InvoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($leaseId, 4, '0', STR_PAD_LEFT);

        return $this->invoiceRepository->create([
            'lease_id' => $leaseId,
            'invoice_number' => $InvoiceNumber,
            'amount' => $amount,
            'status' => InvoiceStatus::UNPAID->value,
            'due_date' => $dueDate,
        ]);
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
                $this->rentalService->activateLease($payment->invoice->lease_id);
                event(new PaymentSettled($payment));
            }

            return $payment;
        });
    }

    public function refundPayment(int $paymentId, string $reason): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason) {
            $payment = $this->paymentRepository->findOrFail($paymentId);

            if ($payment->status !== PaymentStatus::PAID->value || $payment->payment_method !== 'midtrans') {
                throw new \DomainException('Hanya metode Midtrans berstatus lunas yang dapat dikembalikan secara otomatis.');
            }

            try {
                Config::$serverKey = $this->settingService->getMidtransServerKey();
                Config::$isProduction = $this->settingService->isMidtransProduction();

                $params = [
                    'refund_key' => 'refund-' . time() . '-' . $paymentId,
                    'amount'     => (int) $payment->invoice->amount,
                    'reason'     => $reason
                ];

                Transaction::refund($payment->transaction_id, $params);

                $this->paymentRepository->update($payment, [
                    'status'      => PaymentStatus::REFUNDED->value,
                    'admin_notes' => 'Refunded: ' . $reason
                ]);

                $this->invoiceRepository->updateStatus($payment->invoice, InvoiceStatus::UNPAID->value);
                $this->rentalService->cancelLease($payment->invoice->lease_id);

                return $payment;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Refund Error: ' . $e->getMessage());
                throw new \DomainException('Gagal memproses refund ke Midtrans. Saldo mungkin tidak mencukupi atau transaksi belum di-Settle.');
            }
        });
    }

    private function resolveStrategy(string $method): PaymentStrategyInterface
    {
        if ($method === 'midtrans' && !$this->settingService->isMidtransEnabled()) {
            throw new \DomainException('Penyedia layanan (Admin) sedang menonaktifkan fitur pembayaran dengan Midtrans saat ini.');
        }

        return match ($method) {
            'manual'   => app(ManualPaymentStrategy::class),
            'midtrans' => app(MidtransPaymentStrategy::class),
            default    => throw new InvalidArgumentException('Metode tidak didukung'),
        };
    }

    public function handleMidtransNotification(array $payload)
    {
        $orderId = $payload['order_id'];
        $payment = $this->paymentRepository->findByReference($orderId);
        if (!$payment) return;

        $transactionStatus = $payload['transaction_status'];
        $invoice = $payment->invoice;

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $this->paymentRepository->update($payment, ['status' => PaymentStatus::PAID->value]);
            $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::PAID->value);
            $this->rentalService->activateLease($invoice->lease_id);
            event(new PaymentSettled($payment));
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $this->paymentRepository->update($payment, ['status' => PaymentStatus::FAILED->value]);
            $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::UNPAID->value); 
            $this->rentalService->cancelLease($invoice->lease_id);
        }
    }
}
