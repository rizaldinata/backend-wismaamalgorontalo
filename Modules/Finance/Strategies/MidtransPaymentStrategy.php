<?php

namespace Modules\Finance\Strategies;

use App\Contracts\ConfigProviderInterface;
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
use Modules\Finance\Services\MidtransFeeCalculator;

class MidtransPaymentStrategy implements PaymentStrategyInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ConfigProviderInterface $settingService,
        private readonly MidtransFeeCalculator $feeCalculator,
    ) {
        Config::$serverKey = config('finance.midtrans.server_key');
        Config::$isProduction = config('finance.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
        Config::$overrideNotifUrl = config('finance.midtrans.notification_url');
    }

    public function process(Invoice $invoice, array $data): Payment
    {
        // Hitung fee di awal sebelum apapun agar bisa disimpan ke payment record
        $paymentType = $data['payment_type'] ?? null;
        $originalAmount = (int) $invoice->amount;
        $isCustomer = $this->feeCalculator->isCustomerBearer();
        $calculatedFee = $paymentType ? $this->feeCalculator->calculateFee($paymentType, $originalAmount) : 0;
        $chargedToUser = $isCustomer ? $calculatedFee : 0;  // ditambahkan ke tagihan user
        $merchantFee = $isCustomer ? 0 : $calculatedFee;  // dipotong dari penerimaan merchant

        $transactionId = 'TRX-'.time().'-'.$invoice->id;

        $payment = $this->paymentRepository->create([
            'invoice_id' => $invoice->id,
            'payment_method' => PaymentMethod::MIDTRANS->value,
            'status' => PaymentStatus::PENDING->value,
            'transaction_id' => $transactionId,
            'midtrans_fee' => $merchantFee,
            'fee_bearer' => $isCustomer ? 'customer' : 'merchant',
        ]);

        $tenantUser = null;
        if (\App\Support\ModuleGate::isActive('Schedule') && $invoice->schedule_id) {
            $scheduleData = \Modules\Schedule\Services\ScheduleService::getByIds([$invoice->schedule_id]);
            $schedule = $scheduleData[$invoice->schedule_id] ?? null;
            if ($schedule && isset($schedule['tenant_user_id'])) {
                $tenantUser = \Modules\Auth\Models\User::find($schedule['tenant_user_id']);
            }
        }

        $grossAmount = $originalAmount + $chargedToUser;
        $itemDetails = [[
            'id' => $invoice->id,
            'price' => $originalAmount,
            'quantity' => 1,
            'name' => 'Pembayaran Tagihan #'.$invoice->invoice_number,
        ]];

        if ($chargedToUser > 0) {
            $itemDetails[] = [
                'id' => 'FEE-'.$invoice->id,
                'price' => $chargedToUser,
                'quantity' => 1,
                'name' => 'Biaya Transaksi Midtrans',
            ];
        }

        $baseParams = [
            'transaction_details' => [
                'order_id' => $payment->transaction_id,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $invoice->tenant_name ?? '',
                'email' => $tenantUser?->email ?? '',
                'phone' => $invoice->tenant_phone ?? '',
            ],
            'item_details' => $itemDetails,
        ];

        $coreApiExtra = $paymentType ? $this->resolveCoreApiParams($paymentType) : null;

        try {
            if ($coreApiExtra !== null) {
                // ── Core API: langsung charge dengan metode spesifik ──────────
                $params = array_merge($baseParams, $coreApiExtra, [
                    'custom_expiry' => [
                        'order_time' => now()->format('Y-m-d H:i:s O'),
                        'expiry_duration' => 15,
                        'unit' => 'minute',
                    ],
                ]);
                $response = CoreApi::charge($params);
                // Konversi stdClass ke array; model cast 'array' akan handle JSON encoding
                $paymentData = json_decode(json_encode($response), true);

                $this->paymentRepository->update($payment, [
                    'payment_data' => $paymentData,
                ]);
            } else {
                // ── Snap: fallback untuk metode yang tidak didukung Core API ──
                // Jika metode spesifik diketahui (mis. dana, ovo), kunci Snap ke metode itu
                // saja agar fee yang sudah dihitung tetap akurat dan penghuni tidak bisa ganti metode.
                $snapParams = $baseParams;
                if ($paymentType) {
                    $snapParams['enabled_payments'] = [$paymentType];
                } else {
                    $enabledPayments = $this->settingService->getEnabledMidtransPaymentMethods();
                    if (! empty($enabledPayments)) {
                        $snapParams['enabled_payments'] = $enabledPayments;
                    }
                }

                $snapParams['expiry'] = [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit' => 'minutes',
                    'duration' => 15,
                ];

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
