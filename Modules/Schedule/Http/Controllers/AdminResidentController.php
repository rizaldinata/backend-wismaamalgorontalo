<?php

namespace Modules\Schedule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Room\Models\Room;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;

class AdminResidentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status'); // 'Aktif', 'Pending', or null
        $payment = $request->query('payment'); // 'Lunas', 'Belum Lunas', or null
        $perPage = $request->query('per_page', 10);

        // Map status filter
        $scheduleStatus = null;
        if ($status === 'Aktif') {
            $scheduleStatus = ScheduleStatus::ACTIVE;
        } elseif ($status === 'Pending') {
            $scheduleStatus = ScheduleStatus::PENDING;
        }

        // Base query for sewa schedules
        $query = Schedule::with(['tenant', 'room'])
            ->where('type', ScheduleType::SEWA)
            ->whereIn('status', [ScheduleStatus::ACTIVE, ScheduleStatus::PENDING, ScheduleStatus::FINISHED]);

        if ($scheduleStatus) {
            $query->where('status', $scheduleStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('tenant', function ($tenantQ) use ($search) {
                    $tenantQ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                })->orWhereHas('room', function ($roomQ) use ($search) {
                    $roomQ->where('number', 'like', "%{$search}%");
                });
            });
        }

        $schedules = $query->orderBy('created_at', 'desc')->get();

        $scheduleIds = $schedules->pluck('id')->toArray();
        $paymentStatuses = \Modules\Finance\Services\FinanceService::getPaymentStatusByScheduleIds($scheduleIds);

        // Process schedules to append computed detailBayar and format
        $mapped = $schedules->map(function ($schedule) use ($paymentStatuses) {
            $detailBayar = $paymentStatuses[$schedule->id] ?? 'Belum Lunas';

            return [
                'id' => (string) $schedule->id,
                'nama' => $schedule->tenant ? $schedule->tenant->name : $schedule->tenant_name,
                'kamar' => $schedule->room ? $schedule->room->number : '-',
                'kontak' => $schedule->tenant ? $schedule->tenant->phone_number : $schedule->tenant_phone,
                'detailBayar' => $detailBayar,
                'status' => ucfirst($schedule->status->value),
            ];
        });

        // Apply payment filter post-mapping if needed (since it's computed)
        if ($payment === 'Lunas' || $payment === 'Belum Lunas') {
            $mapped = $mapped->where('detailBayar', $payment)->values();
        }

        // Manual pagination over collection
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $paginatedItems = $mapped->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $mapped->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        // Stats
        $penghuniAktif = Schedule::where('type', ScheduleType::SEWA)
            ->where('status', ScheduleStatus::ACTIVE)->count();
        $kontrakPending = Schedule::where('type', ScheduleType::SEWA)
            ->where('status', ScheduleStatus::PENDING)->count();
        $kamarTersedia = Room::where('status', 'available')->count();

        return response()->json([
            'data' => [
                'residents' => $paginator->items(),
                'pagination' => [
                    'currentPage' => $paginator->currentPage(),
                    'lastPage' => $paginator->lastPage(),
                    'perPage' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
                'stats' => [
                    'penghuniAktif' => $penghuniAktif,
                    'kontrakPending' => $kontrakPending,
                    'kamarTersedia' => $kamarTersedia,
                ],
            ],
        ]);
    }
}
