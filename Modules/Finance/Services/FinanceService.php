<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use ManualPaymentStrategy;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Payment;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Strategies\MidtransPaymentStrategy;
use Modules\Rental\Services\RentalService;
use Modules\Setting\Services\SettingService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FinanceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly SettingService $settingService,
    ) {}

    public function processPayment(int $invoiceId, array $data)
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if ($invoice->status === InvoiceStatus::PAID) {
            throw new HttpException(422, 'Tagihan ini sudah lunas.');
        }

        $method = $data['payment_method'];

        if ($method === 'midtrans') {
            if (!$this->settingService->isMidtransEnabled()) {
                throw new HttpException(403, 'Mohon maaf, metode pembayaran saat ini sedang dinonaktifkan oleh admin');
            }
            $strategy = new MidtransPaymentStrategy();
        } elseif ($method === 'manual') {
            $strategy = new ManualPaymentStrategy();
        } else {
            throw new HttpException(422, 'Metode pembayaran tidak didukung');
        }

        return $strategy->process($invoice, $data);
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

    public function verifyPayment(int $paymentId, bool $isApproved, ?string $adminNotes = null)
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->update([
            'status' => $isApproved ? PaymentStatus::VERIFIED->value : PaymentStatus::REJECTED->value,
            'admin_notes' => $adminNotes,
        ]);

        if ($isApproved) {
            $invoice = $this->invoiceRepository->findById($payment->invoice_id);
            $this->invoiceRepository->updateStatus($invoice, InvoiceStatus::PAID->value);

            $rentalService = app(RentalService::class);
            $rentalService->activateLease($invoice->lease_id);
        }

        return $payment;
    }
}
