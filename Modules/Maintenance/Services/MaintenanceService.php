<?php

namespace Modules\Maintenance\Services;

use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Models\MaintenanceSchedule;

class MaintenanceService
{
    /**
     * Get recent damage reports for dashboard (Admin)
     */
    public static function getRecentDamageReports(int $limit = 5): array
    {
        return MaintenanceRequest::with(['room'])
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get recent damage reports reported by specific user (Resident)
     */
    public static function getRecentDamageReportsByReporter(int $userId, int $limit = 5): array
    {
        return MaintenanceRequest::where('reporter_user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get maintenance schedules for this week
     */
    public static function getMaintenanceSchedulesThisWeek(): array
    {
        return MaintenanceSchedule::whereBetween('start_time', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])
            ->orderBy('start_time', 'asc')
            ->get()
            ->toArray();
    }
}
