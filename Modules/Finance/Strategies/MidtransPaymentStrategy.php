<?php

namespace Modules\Finance\Strategies;

use Modules\Finance\Contracts\PaymentStrategyInterface;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class MidtransPaymentStrategy implements PaymentStrategyInterface
{
    public function __construct()
    {
        Config::$serverKey = config('finance.midtrans.server_key');
        Config::$isProduction = config('finance.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function process(Invoice $invoice, array $data): Payment
    {
        // 1. Catat data pembayaran awal di database dengan status PENDING
        $transactionId = 'TRX-' . time() . '-' . $invoice->id;

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'midtrans',
            'status' => 'pending', // Pastikan 'pending' terdaftar di Enum PaymentStatus
            'transaction_id' => $transactionId,
        ]);

        // 2. Siapkan parameter untuk Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $payment->transaction_id,
                'gross_amount' => (int) $invoice->amount,
            ],
            // Catatan: Jika Anda sudah memiliki relasi ke Resident/User,
            // lebih baik ambil nama & email dinamis dari $invoice->lease->resident
            'customer_details' => [
                'first_name' => 'Nama Customer',
                'email' => 'customer@email.com',
            ],
            'item_details' => [
                [
                    'id' => $invoice->id,
                    'price' => (int) $invoice->amount,
                    'quantity' => 1,
                    'name' => 'Pembayaran Tagihan #' . $invoice->invoice_number,
                ]
            ]
        ];

        try {
            // 3. Dapatkan Snap Token dari Midtrans
            $snapToken = Snap::getSnapToken($params);

            // 4. Update data payment dengan snap_token yang didapat (sesuai nama kolom di DB)
            $payment->update([
                'snap_token' => $snapToken
            ]);

            return $payment;
        } catch (Exception $e) {
            // Jika gagal menghubungi Midtrans, ubah status jadi failed
            $payment->update([
                'status' => 'failed',
                'admin_notes' => 'Midtrans Error: ' . $e->getMessage()
            ]);

            throw new \DomainException('Gagal menghubungi server pembayaran: ' . $e->getMessage());
        }
    }
}
