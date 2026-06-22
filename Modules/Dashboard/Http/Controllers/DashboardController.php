<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            $totalResidents = \Modules\Schedule\Models\Schedule::where('type', 'sewa')
                ->where('status', \Modules\Schedule\Enums\ScheduleStatus::ACTIVE)
                ->count();
        }

        // Check if Room module is active
        if ($this->isModuleActive('Room')) {
            $totalRooms = \Modules\Room\Models\Room::count();
            $occupiedRooms = \Modules\Room\Models\Room::where('status', \Modules\Room\Enums\RoomStatus::OCCUPIED)->count();
            $emptyRooms = \Modules\Room\Models\Room::where('status', \Modules\Room\Enums\RoomStatus::AVAILABLE)->count();
        }

        // Check if Finance module is active
        if ($this->isModuleActive('Finance')) {
            $monthlyIncome = \Modules\Finance\Models\Invoice::where('status', \Modules\Finance\Enums\InvoiceStatus::PAID)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');

            // Recent activities (using latest invoices as proxy for activity)
            $recentActivities = \Modules\Finance\Models\Invoice::with(['schedule.room'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'type' => $invoice->type,
                        'amount' => $invoice->amount,
                        'status' => $invoice->status,
                        'tenant_name' => $invoice->tenant_name,
                        'room_number' => $invoice->room_number ?? $invoice->schedule?->room?->number,
                        'created_at' => $invoice->created_at,
                    ];
                });
        }

        $recentDamageReports = [];
        $maintenanceSchedules = [];
        $inventorySummary = null;

        if ($this->isModuleActive('Maintenance')) {
            $recentDamageReports = \Modules\Maintenance\Models\MaintenanceRequest::with(['room'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'title' => $report->title,
                        'reporter_name' => $report->reporter_name,
                        'status' => $report->status->value ?? $report->status,
                        'room_number' => $report->room?->number,
                        'reported_at' => $report->reported_at,
                    ];
                });

            $maintenanceSchedules = \Modules\Maintenance\Models\MaintenanceSchedule::whereBetween('start_time', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'technician_name' => $schedule->technician_name,
                        'location' => $schedule->location,
                        'type' => $schedule->type->value ?? $schedule->type,
                        'subtype' => $schedule->subtype->value ?? $schedule->subtype,
                        'status' => $schedule->status->value ?? $schedule->status,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                    ];
                });
        }

        if ($this->isModuleActive('Inventory')) {
            $totalItems = \Modules\Inventory\Models\Inventory::sum('quantity');
            $brokenItems = \Modules\Inventory\Models\Inventory::where('condition', '!=', \Modules\Inventory\Enums\ItemCondition::GOOD->value ?? 'good')->sum('quantity');

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
            $activeSchedule = \Modules\Schedule\Models\Schedule::where('tenant_user_id', $userId)
                ->where('status', \Modules\Schedule\Enums\ScheduleStatus::ACTIVE)
                ->with('room')
                ->first();

            if ($activeSchedule && $activeSchedule->room) {
                $activeRoomData = [
                    'room_number' => $activeSchedule->room->number,
                    'status' => 'Aktif',
                ];
            }
        }

        // Get recent bills if Finance module is active
        if ($this->isModuleActive('Finance')) {
            $recentBills = \Modules\Finance\Models\Invoice::where('tenant_user_id', $userId)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'title' => 'Tagihan ' . ($invoice->type->value ?? 'Sewa'),
                        'amount' => $invoice->amount,
                        'status' => $invoice->status,
                        'created_at' => $invoice->created_at,
                    ];
                });
        }

        $recentDamageReports = [];
        $maintenanceSchedules = [];
        $recentGuests = [];

        if ($this->isModuleActive('Maintenance')) {
            $recentDamageReports = \Modules\Maintenance\Models\MaintenanceRequest::where('reporter_user_id', $userId)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'title' => $report->title,
                        'status' => $report->status->value ?? $report->status,
                        'reported_at' => $report->reported_at,
                    ];
                });

            $maintenanceSchedules = \Modules\Maintenance\Models\MaintenanceSchedule::whereBetween('start_time', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'technician_name' => $schedule->technician_name,
                        'location' => $schedule->location,
                        'type' => $schedule->type->value ?? $schedule->type,
                        'subtype' => $schedule->subtype->value ?? $schedule->subtype,
                        'status' => $schedule->status->value ?? $schedule->status,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                    ];
                });
        }

        if ($this->isModuleActive('Guest')) {
            $recentGuests = \Modules\Guest\Models\Guest::where('user_id', $userId)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($guest) {
                    return [
                        'id' => $guest->id,
                        'name' => $guest->name,
                        'relationship' => $guest->relationship->value ?? $guest->relationship,
                        'check_in_at' => $guest->check_in_at,
                        'check_out_at' => $guest->check_out_at,
                    ];
                });
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
        if (!Module::has($moduleName) || !Module::isEnabled($moduleName)) {
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
