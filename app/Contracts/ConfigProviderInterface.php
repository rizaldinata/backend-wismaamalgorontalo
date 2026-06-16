<?php

namespace App\Contracts;

/**
 * Abstraksi akses konfigurasi aplikasi untuk modul bisnis.
 * Modul bisnis depend pada interface ini, bukan langsung ke SettingService.
 */
interface ConfigProviderInterface
{
    public function isFeatureEnabled(string $key): bool;

    public function getSettingValue(string $key, mixed $default = ''): mixed;
}
