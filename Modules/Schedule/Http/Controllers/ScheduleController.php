<?php

namespace Modules\Schedule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Schedule\Services\ScheduleService;
use Modules\Schedule\Transformers\ScheduleResource;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ScheduleService $scheduleService) {}

    public function index(Request $request): Response|JsonResponse
    {
        $schedules = $this->scheduleService->ambilSemuaJadwal($request->only(['room_id', 'type', 'status', 'per_page']));

        return ScheduleResource::collection($schedules)
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|integer',
            'type' => 'required|in:sewa,maintenance,kebersihan,blokir',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'tenant_name' => 'nullable|string|max:255',
            'tenant_id_number' => 'nullable|string|max:50',
            'tenant_phone' => 'nullable|string|max:20',
            'tenant_user_id' => 'nullable|integer|exists:users,id',
            'agreed_price' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = Auth::id();

        $schedule = $this->scheduleService->buatJadwal($validated);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil dibuat', 201);
    }

    public function show(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->ambilJadwalById($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Detail jadwal');
    }

    public function aktifkan(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->aktifkanJadwal($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil diaktifkan');
    }

    public function selesaikan(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->selesaikanJadwal($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil diselesaikan');
    }

    public function batalkan(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->batalkanJadwal($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil dibatalkan');
    }

    public function mySchedules(Request $request): Response|JsonResponse
    {
        $filters = array_merge(
            $request->only(['type', 'status', 'per_page']),
            ['tenant_user_id' => Auth::id()]
        );

        $schedules = $this->scheduleService->ambilSemuaJadwal($filters);

        return ScheduleResource::collection($schedules)
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    public function byKamar(int $roomId): JsonResponse
    {
        $schedules = $this->scheduleService->ambilJadwalKamar($roomId);

        return $this->apiSuccess(ScheduleResource::collection($schedules), 'Daftar jadwal kamar');
    }
}
