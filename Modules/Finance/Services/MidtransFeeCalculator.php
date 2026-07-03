<?php

namespace Modules\Finance\Services;

use App\Contracts\ConfigProviderInterface;

class MidtransFeeCalculator
{
    private array $config;

    private const BANK_TRANSFER_METHODS = [
        'bca_va', 'bni_va', 'bri_va', 'permata_va', 'mandiri_va',
    ];

    public function __construct(ConfigProviderInterface $settingService)
    {
        $this->config = $settingService->getMidtransFeeConfig();
    }

    public function isCustomerBearer(): bool
    {
        return ($this->config['bearer'] ?? 'merchant') === 'customer';
    }

    public function calculateFee(string $paymentMethod, float $amount): int
    {
        $feeKey = in_array($paymentMethod, self::BANK_TRANSFER_METHODS, true)
            ? 'bank_transfer'
            : $paymentMethod;

        $feeDef = $this->config['fees'][$feeKey] ?? null;

        if (! $feeDef) {
            return 0;
        }

        return match ($feeDef['type']) {
            'flat' => (int) ($feeDef['amount'] ?? 0),
            'percent' => (int) round($amount * ($feeDef['rate'] ?? 0) / 100),
            default => 0,
        };
    }
}
