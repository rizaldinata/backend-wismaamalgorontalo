<?php

namespace Modules\Finance\Strategies;

use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Finance\Contracts\PaymentStrategyInterface;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class MidtransPaymentStrategy implements PaymentStrategyInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {
        Config::$serverKey = config('finance.midtrans.server_key');
        Config::$isProduction = config('finance.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
        Config::$overrideNotifUrl = config('finance.midtrans.notification_url');
    }

    public function process(Invoice $invoice, array $data): Payment
    {
        // 1. Catat data pembayaran awal di database dengan status PENDING
        $transactionId = 'TRX-' . time() . '-' . $invoice->id;

        $payment = $this->paymentRepository->create([
            'invoice_id'     => $invoice->id,
            'payment_method' => PaymentMethod::MIDTRANS->value,
            'status'         => PaymentStatus::PENDING->value,
            'transaction_id' => $transactionId,
        ]);

        // 2. Siapkan parameter untuk Midtrans
        $invoice->loadMissing('lease.resident.user');
        $resident = $invoice->lease->resident;
        $user     = $resident->user;

        $params = [
            'transaction_details' => [
                'order_id'     => $payment->transaction_id,
                'gross_amount' => (int) $invoice->amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email'      => $user->email,
                'phone'      => $resident->phone_number,
            ],
            'item_details' => [
                [
                    'id'       => $invoice->id,
                    'price'    => (int) $invoice->amount,
                    'quantity' => 1,
                    'name'     => 'Pembayaran Tagihan #' . $invoice->invoice_number,
                ]
            ]
        ];

        $enabledPayments = config('finance.midtrans.enabled_payments', []);
        if (!empty($enabledPayments)) {
            $params['enabled_payments'] = $enabledPayments;
        }

        try {
            // 3. Dapatkan Snap Token dari Midtrans
            $snapToken = Snap::getSnapToken($params);

            // 4. Update data payment dengan snap_token yang didapat (sesuai nama kolom di DB)
            $this->paymentRepository->update($payment, [
                'snap_token' => $snapToken
            ]);

            return $payment;
        } catch (Exception $e) {
            // Jika gagal menghubungi Midtrans, ubah status jadi failed
            $this->paymentRepository->update($payment, [
                'status'      => PaymentStatus::FAILED->value,
                'admin_notes' => 'Midtrans Error: ' . $e->getMessage(),
            ]);

            throw new \DomainException('Gagal memproses metode pembayaran. Silakan coba kembali beberapa saat lagi.');
        }
    }
}
