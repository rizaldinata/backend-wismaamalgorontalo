<?php

namespace Modules\Schedule\Services;

use App\Contracts\ActiveTenantCheckerInterface;
use Illuminate\Support\Facades\DB;

class ScheduleActiveTenantChecker implements ActiveTenantCheckerInterface
{
    public function isActiveTenant(int $userId): bool
    {
        return DB::table('room_schedules')
            ->where('tenant_user_id', $userId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->startOfDay());
            })
            ->exists();
    }
}
