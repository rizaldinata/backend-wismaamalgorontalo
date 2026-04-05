<?php

namespace Modules\Setting\Repositories;

use Modules\Setting\Models\AppSetting;
use Modules\Setting\Repositories\Contracts\SettingRepositoryInterface;

class SettingRepository implements SettingRepositoryInterface
{
    public function getValueByKey(string $key, $default = null)
    {
        $setting = AppSetting::where('key', $key)->first();
        return $setting ? $setting->parsed_value : $default;
    }

    public function updateOrCreate(string $key, string $value, ?string $description = null)
    {
        return AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );
    }
}
