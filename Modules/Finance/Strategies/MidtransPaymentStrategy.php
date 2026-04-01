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
        // Gunakan variabel sementara untuk ID transaksi
        $transactionId = 'TRX-' . time() . '-' . $invoice->id;

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            // Hapus 'amount' karena tidak ada di $fillable tabel Payment
            'payment_method' => 'midtrans',
            'status' => 'pending',
            'transaction_id' => $transactionId, // Ganti reference_id menjadi transaction_id
        ]);

        // 2. Siapkan parameter untuk Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $payment->transaction_id, // Panggil transaction_id
                'gross_amount' => (int) $invoice->amount, // Ambil amount langsung dari relasi $invoice
            ],
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

            // 4. Pastikan kolom payment_gateway_token ada di database dan fillable
            $payment->update(['payment_gateway_token' => $snapToken]);

            return $payment;
        } catch (Exception $e) {
            $payment->update(['status' => 'failed', 'admin_notes' => $e->getMessage()]);
            throw new \DomainException('Gagal menghubungi server pembayaran: ' . $e->getMessage());
        }
    }
}
