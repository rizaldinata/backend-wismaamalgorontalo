<?php

namespace Modules\Resident\Listeners;

use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Events\LeaseEnded;

class DowngradeToMember
{
    public function handle(LeaseEnded $event): void
    {
        $lease = $event->lease;
        $resident = $lease->resident;
        if (!$resident) return;

        $user = $resident->user;
        if (!$user || !$user->hasRole('resident')) return;

        // Tetap resident jika masih punya sewa aktif lain
        $hasOtherActiveLease = $resident->leases()
            ->where('status', LeaseStatus::ACTIVE->value)
            ->where('id', '!=', $lease->id)
            ->exists();

        if ($hasOtherActiveLease) return;

        $user->removeRole('resident');
        $user->assignRole('member');
    }
}
