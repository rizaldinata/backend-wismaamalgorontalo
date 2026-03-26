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
        // Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function process(Invoice $invoice, array $data): Payment
    {
        // 1. Catat data pembayaran awal di database dengan status PENDING
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'payment_method' => 'midtrans',
            'status' => 'pending', // Anda bisa sesuaikan dengan Enum PaymentStatus
            'reference_id' => 'TRX-' . time() . '-' . $invoice->id, // Order ID unik
        ]);

        // 2. Siapkan parameter untuk Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $payment->reference_id,
                'gross_amount' => (int) $payment->amount,
            ],
            'customer_details' => [
                'first_name' => 'Nama Customer', // Bisa diambil dari relasi User/Customer
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

            // 4. Simpan token ke database agar bisa diakses kapan saja
            $payment->update(['payment_gateway_token' => $snapToken]);

            return $payment;
        } catch (Exception $e) {
            $payment->update(['status' => 'failed', 'admin_notes' => $e->getMessage()]);
            throw new \DomainException('Gagal menghubungi server pembayaran: ' . $e->getMessage());
        }
    }
}
