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

    public function isMidtransEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_midtrans_payment');
    }

    public function isDailyRentalEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_daily_rental');
    }
}
