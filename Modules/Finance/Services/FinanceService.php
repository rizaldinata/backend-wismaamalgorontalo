<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Contracts\PaymentStrategyInterface;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Events\PaymentSettled;
use Modules\Finance\Models\Payment; // dibutuhkan sebagai return type hint
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Strategies\ManualPaymentStrategy;
use Modules\Finance\Strategies\MidtransPaymentStrategy;
use Modules\Rental\Services\RentalService;

class FinanceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly RentalService $rentalService,
        private readonly PaymentRepositoryInterface $paymentRepository
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

    private function resolveStrategy(string $method): PaymentStrategyInterface
    {
        return match ($method) {
            'manual'   => app(ManualPaymentStrategy::class),
            'midtrans' => app(MidtransPaymentStrategy::class),
            default    => throw new InvalidArgumentException('Metode tidak didukung'),
        };
    }

    public function handleMidtransNotification(array $payload)
    {
        if (!isset($payload['order_id']) || !isset($payload['signature_key'])) {
            return;
        }

        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'];
        $grossAmount = $payload['gross_amount'];
        $serverKey = config('finance.midtrans.server_key');

        // 1. Verifikasi Signature Key (Wajib demi keamanan!)
        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($signatureKey !== $payload['signature_key']) {
            throw new \DomainException('Invalid Signature');
        }

        // 2. Cari data pembayaran berdasarkan Order ID
        $payment = $this->paymentRepository->findByReference($orderId);
        if (!$payment) return;

        $transactionStatus = $payload['transaction_status'];
        $invoice = $payment->invoice;

        // 3. Update status pembayaran dan invoice
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $this->paymentRepository->update($payment, ['status' => PaymentStatus::PAID->value]);
            $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::PAID->value);

            // Panggil fungsi untuk mengaktifkan sewa (kamar jadi tidak tersedia)
            $this->rentalService->activateLease($invoice->lease_id);
            event(new PaymentSettled($payment));
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $this->paymentRepository->update($payment, ['status' => PaymentStatus::FAILED->value]);
            $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::UNPAID->value); // Kembali ke unpaid agar bisa dibayar ulang

            // Panggil fungsi untuk membatalkan sewa (kamar kembali tersedia)
            $this->rentalService->cancelLease($invoice->lease_id);
        }
    }
}
