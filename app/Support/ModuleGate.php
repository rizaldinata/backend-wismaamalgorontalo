<?php

namespace App\Support;

use Nwidart\Modules\Facades\Module;

class ModuleGate
{
    /**
     * Mengecek apakah sebuah modul terinstall dan sedang aktif.
     *
     * @param  string  $moduleName  Nama modul (misal: 'Schedule', 'Finance')
     */
    public static function isActive(string $moduleName): bool
    {
        return Module::has($moduleName) && Module::isEnabled($moduleName);
    }
}
