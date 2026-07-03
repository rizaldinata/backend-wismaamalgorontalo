<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Maintenance\Http\Requests\StoreScheduleRequest;
use Modules\Maintenance\Http\Requests\StoreScheduleUpdate;
use Modules\Maintenance\Http\Requests\UpdateScheduleRequest;
use Modules\Maintenance\Services\ScheduleService;
use Modules\Maintenance\Transformers\ScheduleResource;
use Modules\Maintenance\Transformers\ScheduleUpdateResource;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ScheduleService $scheduleService
    ) {}

    /**
     * Daftar Jadwal Maintenance
     *
     * Mengambil daftar seluruh jadwal perbaikan fasilitas. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $perPage = request()->get('per_page', 10);
        $schedules = $this->scheduleService->getPaginated($perPage);
        $resource = ScheduleResource::collection($schedules)->response()->getData(true);

        return $this->apiSuccess($resource['data'], 'Daftar jadwal berhasil diambil.', 200, $resource['meta']);
    }

    /**
     * Tambah Jadwal Maintenance
     *
     * Membuat jadwal perbaikan fasilitas baru. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreScheduleRequest $request)
    {
        $schedule = $this->scheduleService->create(Auth::id(), $request->validated());

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil ditambahkan.', 201);
    }

    /**
     * Detail Jadwal Maintenance
     *
     * Mengambil detail jadwal perbaikan beserta update progress-nya. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $schedule = $this->scheduleService->findById($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Detail jadwal berhasil diambil.');
    }

    /**
     * Edit Jadwal Maintenance
     *
     * Mengubah data jadwal perbaikan (contoh: status, tanggal, dll). (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateScheduleRequest $request, int $id)
    {
        $schedule = $this->scheduleService->update($id, $request->validated());

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil diperbarui.');
    }

    /**
     * Hapus Jadwal Maintenance
     *
     * Menghapus secara permanen jadwal perbaikan. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $this->scheduleService->delete($id);

        return $this->apiSuccess(null, 'Jadwal berhasil dihapus.');
    }

    /**
     * Tambah Update Progress
     *
     * Menambahkan log/update terkait progress perbaikan (contoh: "Tukang sudah datang"). (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeUpdate(StoreScheduleUpdate $request, int $id)
    {
        $update = $this->scheduleService->addUpdate(Auth::id(), $id, $request->validated());

        return $this->apiSuccess(new ScheduleUpdateResource($update), 'Update berhasil ditambahkan.', 201);
    }
}
