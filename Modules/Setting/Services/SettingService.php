<?php

namespace Modules\Setting\Services;

use Modules\Setting\Repositories\Contracts\SettingRepositoryInterface;
use Modules\Setting\Repositories\SettingRepository;

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
            'feature_daily_rental' => $this->isDailyRentalEnabled()
        ];
    }


    public function isDailyRentalEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_daily_rental');
    }
}
