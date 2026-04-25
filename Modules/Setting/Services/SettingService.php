<?php

namespace Modules\Setting\Services;

use Modules\Setting\Repositories\Contracts\SettingRepositoryInterface;
use Modules\Setting\Repositories\SettingRepository;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    public function __construct(
        private readonly SettingRepositoryInterface $settingRepository
    ) {}

    public function isFeatureEnabled(string $featureKey): bool
    {
        return (bool) $this->settingRepository->getValueByKey($featureKey, false);
    }

    public function setFeatureState(string $featureKey, bool $isEnabled, string $description = ''): void
    {
        $valueString = $isEnabled ? 'true' : 'false';
        $this->settingRepository->updateOrCreate($featureKey, $valueString, $description);
    }

    public function updateSetting(string $key, $value, string $description = ''): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        // Terapkan enkripsi untuk data keuangan sensitif
        if (in_array($key, ['midtrans_server_key', 'midtrans_client_key']) && !empty($value)) {
            $value = Crypt::encryptString((string)$value);
        }

        $this->settingRepository->updateOrCreate($key, (string)$value, $description);
    }

    public function getSettingValue(string $key, $default = '')
    {
        return $this->settingRepository->getValueByKey($key, $default);
    }

    public function getPublicSettings(): array
    {
        // Menyediakan rincian konfigurasi ke Frontend tanpa membahayakan data enkripsi
        return [
            'wisma_name' => $this->getSettingValue('wisma_name', 'Sistem Manajemen Kos'),
            'feature_midtrans_payment' => $this->isMidtransEnabled(),
            'midtrans_is_production' => $this->isMidtransProduction(),
            'feature_daily_rental' => $this->isDailyRentalEnabled(),
            'midtrans_enabled_payments' => $this->getMidtransEnabledPayments(),
            // Mask the server key so it isn't leaked to front-end HTTP calls
            'midtrans_server_key' => $this->getMidtransServerKey() ? '********' : '',
            // The Client Key is technically safe to be exposed but mask it partially if desired, normally it is exposed to frontend Snap JS
            'midtrans_client_key' => $this->getMidtransClientKey()
        ];
    }

    public function getMidtransServerKey(): string
    {
        $encrypted = $this->settingRepository->getValueByKey('midtrans_server_key', '');
        if (empty($encrypted)) return config('finance.midtrans.server_key', '');

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return config('finance.midtrans.server_key', '');
        }
    }

    public function getMidtransClientKey(): string
    {
        $encrypted = $this->settingRepository->getValueByKey('midtrans_client_key', '');
        if (empty($encrypted)) return config('finance.midtrans.client_key', '');

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return config('finance.midtrans.client_key', '');
        }
    }

    public function isMidtransProduction(): bool
    {
        $val = $this->settingRepository->getValueByKey('midtrans_is_production', '');
        if ($val === '') return config('finance.midtrans.is_production', false);
        return $val === 'true';
    }

    public function isMidtransEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_midtrans_payment');
    }

    public function getMidtransEnabledPayments(): array
    {
        $value = $this->settingRepository->getValueByKey('midtrans_enabled_payments', '[]');
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function isDailyRentalEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_daily_rental');
    }
}
