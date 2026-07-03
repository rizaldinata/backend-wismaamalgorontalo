<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Nwidart\Modules\Facades\Module;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics.
     */
    public function getAdminStats(): JsonResponse
    {
        $totalRooms = 0;
        $occupiedRooms = 0;
        $emptyRooms = 0;
        $monthlyIncome = 0;
        $recentActivities = [];

        $totalResidents = User::role('resident')->count();
        if ($this->isModuleActive('Schedule')) {
            $totalResidents = \Modules\Schedule\Services\ScheduleService::getActiveSewaCount();
        }

        // Check if Room module is active
        if ($this->isModuleActive('Room')) {
            $totalRooms = \Modules\Room\Services\RoomService::getTotalRoomsCount();
            $occupiedRooms = \Modules\Room\Services\RoomService::getOccupiedRoomsCount();
            $emptyRooms = \Modules\Room\Services\RoomService::getAvailableRoomsCount();
        }

        // Check if Finance module is active
        if ($this->isModuleActive('Finance')) {
            $monthlyIncome = \Modules\Finance\Services\FinanceService::getMonthlyIncome();

            $recentActivities = collect(\Modules\Finance\Services\FinanceService::getRecentActivities(5))
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice['id'],
                        'title' => 'Pembayaran '.($invoice['type'] ?? 'Sewa'),
                        'amount' => $invoice['amount'],
                        'status' => $invoice['status'],
                        'tenant_name' => $invoice['tenant_name'],
                        'room_number' => $invoice['schedule_data']['room']['number'] ?? null,
                        'date' => $invoice['created_at'],
                    ];
                });
        }

        $recentDamageReports = [];
        $maintenanceSchedules = [];
        $inventorySummary = null;

        if ($this->isModuleActive('Maintenance')) {
            $recentDamageReports = collect(\Modules\Maintenance\Services\MaintenanceService::getRecentDamageReports(5))
                ->map(function ($report) {
                    return [
                        'id' => $report['id'],
                        'title' => $report['title'],
                        'reporter_name' => $report['reporter_name'],
                        'status' => $report['status'],
                        'room_number' => $report['room']['number'] ?? null,
                        'reported_at' => $report['reported_at'],
                    ];
                });

            $maintenanceSchedules = collect(\Modules\Maintenance\Services\MaintenanceService::getMaintenanceSchedulesThisWeek())
                ->map(function ($schedule) {
                    return [
                        'id' => $schedule['id'],
                        'technician_name' => $schedule['technician_name'],
                        'location' => $schedule['location'],
                        'type' => $schedule['type'],
                        'subtype' => $schedule['subtype'],
                        'status' => $schedule['status'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                    ];
                });
        }

        if ($this->isModuleActive('Inventory')) {
            $totalItems = \Modules\Inventory\Services\InventoryService::getTotalItems();
            $brokenItems = \Modules\Inventory\Services\InventoryService::getBrokenItems();

            $inventorySummary = [
                'total_items' => (int) $totalItems,
                'broken_items' => (int) $brokenItems,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => [
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'empty_rooms' => $emptyRooms,
                'total_residents' => $totalResidents,
                'monthly_income' => $monthlyIncome,
                'recent_activities' => $recentActivities,
                'recent_damage_reports' => $recentDamageReports,
                'maintenance_schedules' => $maintenanceSchedules,
                'inventory_summary' => $inventorySummary,
                'is_maintenance_active' => $this->isModuleActive('Maintenance'),
                'is_inventory_active' => $this->isModuleActive('Inventory'),
            ],
        ]);
    }

    /**
     * Get resident dashboard statistics.
     */
    public function getResidentStats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $activeRoomData = null;
        $recentBills = [];

        // Get the active room schedule if Schedule module is active
        if ($this->isModuleActive('Schedule')) {
            $activeSchedule = \Modules\Schedule\Services\ScheduleService::getActiveByTenantUserId($userId);

            if ($activeSchedule && isset($activeSchedule['room'])) {
                $activeRoomData = [
                    'room_number' => $activeSchedule['room']['number'] ?? '',
                    'status' => 'Aktif',
                ];
            }
        }

        // Get recent bills if Finance module is active
        if ($this->isModuleActive('Finance')) {
            $recentBills = collect(\Modules\Finance\Services\FinanceService::getRecentBills($userId, 5))
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice['id'],
                        'title' => 'Tagihan '.($invoice['type'] ?? 'Sewa'),
                        'amount' => $invoice['amount'],
                        'status' => $invoice['status'],
                        'created_at' => $invoice['created_at'],
                    ];
                });
        }

        $recentDamageReports = [];
        $maintenanceSchedules = [];
        $recentGuests = [];

        if ($this->isModuleActive('Maintenance')) {
            $recentDamageReports = collect(\Modules\Maintenance\Services\MaintenanceService::getRecentDamageReportsByReporter($userId, 5))
                ->map(function ($report) {
                    return [
                        'id' => $report['id'],
                        'title' => $report['title'],
                        'status' => $report['status'],
                        'reported_at' => $report['reported_at'],
                    ];
                });

            $maintenanceSchedules = collect(\Modules\Maintenance\Services\MaintenanceService::getMaintenanceSchedulesThisWeek())
                ->map(function ($schedule) {
                    return [
                        'id' => $schedule['id'],
                        'technician_name' => $schedule['technician_name'],
                        'location' => $schedule['location'],
                        'type' => $schedule['type'],
                        'status' => $schedule['status'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                    ];
                });
        }

        if ($this->isModuleActive('Guest')) {
            $recentGuests = \Modules\Guest\Services\GuestService::getRecentGuestsByUserId($userId, 5);
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => [
                'active_room' => $activeRoomData,
                'recent_bills' => $recentBills,
                'recent_damage_reports' => $recentDamageReports,
                'maintenance_schedules' => $maintenanceSchedules,
                'recent_guests' => $recentGuests,
                'is_maintenance_active' => $this->isModuleActive('Maintenance'),
                'is_guest_active' => $this->isModuleActive('Guest'),
            ],
        ]);
    }

    /**
     * Safely check if a module is active and its classes are accessible.
     */
    private function isModuleActive(string $moduleName): bool
    {
        if (! Module::has($moduleName) || ! Module::isEnabled($moduleName)) {
            return false;
        }

        // Also fallback to check via our custom FeatureToggleService if Setting module is active
        if (Module::has('Setting') && Module::isEnabled('Setting') && class_exists(\Modules\Setting\Services\FeatureToggleService::class)) {
            $service = app(\Modules\Setting\Services\FeatureToggleService::class);

            $key = strtolower($moduleName);
            $toggleKeys = [
                'maintenance' => 'damage_report',
                'inventory' => 'inventory',
                'guest' => 'guest',
                'schedule' => 'schedule',
                'room' => 'room',
                'finance' => 'finance',
            ];

            $featureKey = $toggleKeys[$key] ?? $key;

            return $service->isEnabled($featureKey);
        }

        return true;
    }
}
