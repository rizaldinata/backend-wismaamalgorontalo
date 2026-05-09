<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Maintenance\Http\Requests\StoreScheduleRequest;
use Modules\Maintenance\Http\Requests\UpdateScheduleRequest;
use Modules\Maintenance\Http\Requests\StoreScheduleUpdate;
use Modules\Maintenance\Services\ScheduleService;
use Modules\Maintenance\Transformers\ScheduleResource;
use Modules\Maintenance\Transformers\ScheduleUpdateResource;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ScheduleService $scheduleService
    ) {}

    public function index()
    {
        $schedules = $this->scheduleService->getAll();
        return $this->apiSuccess(ScheduleResource::collection($schedules), 'Daftar jadwal berhasil diambil.');
    }

    public function store(StoreScheduleRequest $request)
    {
        $schedule = $this->scheduleService->create(Auth::id(), $request->validated());
        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil ditambahkan.', 201);
    }

    public function show(int $id)
    {
        $schedule = $this->scheduleService->findById($id);
        return $this->apiSuccess(new ScheduleResource($schedule), 'Detail jadwal berhasil diambil.');
    }

    public function update(UpdateScheduleRequest $request, int $id)
    {
        $schedule = $this->scheduleService->update($id, $request->validated());
        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $this->scheduleService->delete($id);
        return $this->apiSuccess(null, 'Jadwal berhasil dihapus.');
    }

    public function storeUpdate(StoreScheduleUpdate $request, int $id)
    {
        $update = $this->scheduleService->addUpdate(Auth::id(), $id, $request->validated());
        return $this->apiSuccess(new ScheduleUpdateResource($update), 'Update berhasil ditambahkan.', 201);
    }
}
