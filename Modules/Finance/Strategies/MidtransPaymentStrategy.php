<?php

namespace Modules\Finance\Strategies;

use Exception;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;
use Modules\Auth\Models\User;
use Modules\Finance\Contracts\PaymentStrategyInterface;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;

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
        $transactionId = 'TRX-'.time().'-'.$invoice->id;

        $payment = $this->paymentRepository->create([
            'invoice_id' => $invoice->id,
            'payment_method' => PaymentMethod::MIDTRANS->value,
            'status' => PaymentStatus::PENDING->value,
            'transaction_id' => $transactionId,
        ]);

        $invoice->loadMissing('schedule');
        $schedule = $invoice->schedule;
        $tenantUser = $schedule?->tenant_user_id ? User::find($schedule->tenant_user_id) : null;

        $baseParams = [
            'transaction_details' => [
                'order_id' => $payment->transaction_id,
                'gross_amount' => (int) $invoice->amount,
            ],
            'customer_details' => [
                'first_name' => $schedule?->tenant_name ?? '',
                'email' => $tenantUser?->email ?? '',
                'phone' => $schedule?->tenant_phone ?? '',
            ],
            'item_details' => [[
                'id' => $invoice->id,
                'price' => (int) $invoice->amount,
                'quantity' => 1,
                'name' => 'Pembayaran Tagihan #'.$invoice->invoice_number,
            ]],
        ];

        $paymentType = $data['payment_type'] ?? null;
        $coreApiExtra = $paymentType ? $this->resolveCoreApiParams($paymentType) : null;

        try {
            if ($coreApiExtra !== null) {
                // ── Core API: langsung charge dengan metode spesifik ──────────
                $params = array_merge($baseParams, $coreApiExtra);
                $response = CoreApi::charge($params);
                // Konversi stdClass ke array; model cast 'array' akan handle JSON encoding
                $paymentData = json_decode(json_encode($response), true);

                $this->paymentRepository->update($payment, [
                    'payment_data' => $paymentData,
                ]);
            } else {
                // ── Snap: fallback untuk metode yang tidak didukung Core API ──
                $enabledPayments = config('finance.midtrans.enabled_payments', []);
                $snapParams = $baseParams;
                if (! empty($enabledPayments)) {
                    $snapParams['enabled_payments'] = $enabledPayments;
                }

                $snapToken = Snap::getSnapToken($snapParams);
                $this->paymentRepository->update($payment, [
                    'snap_token' => $snapToken,
                ]);
            }

            return $payment;
        } catch (Exception $e) {
            $this->paymentRepository->update($payment, [
                'status' => PaymentStatus::FAILED->value,
                'admin_notes' => 'Midtrans Error: '.$e->getMessage(),
            ]);

            throw new \DomainException('Gagal memproses metode pembayaran. Silakan coba kembali beberapa saat lagi.');
        }
    }

    /**
     * Map kode metode dari form ke parameter Core API.
     * Kembalikan null jika metode tidak didukung Core API (→ gunakan Snap).
     */
    private function resolveCoreApiParams(string $method): ?array
    {
        return match ($method) {
            'qris' => [
                'payment_type' => 'qris',
                'qris' => ['acquirer' => 'gopay'],
            ],
            'gopay' => [
                'payment_type' => 'gopay',
                'gopay' => ['enable_callback' => false],
            ],
            'shopeepay' => [
                'payment_type' => 'shopeepay',
                'shopeepay' => ['callback_url' => ''],
            ],
            'bca_va' => [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => ['bank' => 'bca'],
            ],
            'bni_va' => [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => ['bank' => 'bni'],
            ],
            'bri_va' => [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => ['bank' => 'bri'],
            ],
            'permata_va' => [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => ['bank' => 'permata'],
            ],
            'other_va' => [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => ['bank' => 'bca'],
            ],
            'mandiri_va', 'echannel' => [
                'payment_type' => 'echannel',
                'echannel' => [
                    'bill_info1' => 'Pembayaran Tagihan',
                    'bill_info2' => 'Wisma Amal',
                ],
            ],
            'alfamart' => [
                'payment_type' => 'cstore',
                'cstore' => ['store' => 'Alfamart'],
            ],
            'indomaret' => [
                'payment_type' => 'cstore',
                'cstore' => ['store' => 'Indomaret'],
            ],
            'credit_card' => [
                'payment_type' => 'credit_card',
                'credit_card' => ['secure' => true],
            ],
            // DANA, OVO, LinkAja tidak didukung Core API → gunakan Snap
            default => null,
        };
    }
}
