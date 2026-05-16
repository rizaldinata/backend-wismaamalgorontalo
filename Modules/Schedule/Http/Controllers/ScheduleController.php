<?php

namespace Modules\Schedule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Schedule\Services\ScheduleService;
use Modules\Schedule\Transformers\ScheduleResource;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ScheduleService $scheduleService) {}

    public function index(Request $request): JsonResponse
    {
        $schedules = $this->scheduleService->ambilSemuaJadwal($request->only(['room_id', 'type', 'status']));

        return $this->apiSuccess(ScheduleResource::collection($schedules), 'Daftar jadwal berhasil diambil');
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
            'agreed_price' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = Auth::id();

        $schedule = $this->scheduleService->buatJadwal($validated);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil dibuat', 201);
    }

    public function show(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->ambilJadwalAktifKamar($id)
            ?? $this->scheduleService->ambilJadwalKamar($id);

        if (is_iterable($schedule) && ! ($schedule instanceof \Modules\Schedule\Models\Schedule)) {
            return $this->apiSuccess(ScheduleResource::collection($schedule), 'Daftar jadwal kamar');
        }

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

    public function byKamar(int $roomId): JsonResponse
    {
        $schedules = $this->scheduleService->ambilJadwalKamar($roomId);

        return $this->apiSuccess(ScheduleResource::collection($schedules), 'Daftar jadwal kamar');
    }
}
