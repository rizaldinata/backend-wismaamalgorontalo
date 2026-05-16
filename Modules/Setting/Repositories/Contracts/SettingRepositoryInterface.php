<?php

namespace Modules\Setting\Repositories\Contracts;

interface SettingRepositoryInterface
{
    public function getValueByKey(string $key, $default = null);

    public function updateOrCreate(string $key, string $value, ?string $description = null);
}
