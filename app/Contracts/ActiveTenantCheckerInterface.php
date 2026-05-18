<?php

namespace App\Contracts;

/**
 * Contract untuk mengecek apakah seorang user adalah penghuni aktif.
 * Modul Core Schedule mengimplementasi ini; app-level code (Gate) hanya bergantung pada interface ini.
 */
interface ActiveTenantCheckerInterface
{
    public function isActiveTenant(int $userId): bool;
}
