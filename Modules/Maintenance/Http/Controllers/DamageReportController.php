<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Maintenance\Http\Requests\StoreMaintenanceRequest;
use Modules\Maintenance\Services\DamageReportService;
use Modules\Maintenance\Transformers\MaintenanceRequestResource;

class DamageReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DamageReportService $maintenanceService
    ) {}

    /**
     * Laporan Kerusakan Saya
     *
     * Melihat riwayat laporan kerusakan (maintenance) milik pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
    public function myReports()
    {
        $reports = $this->maintenanceService->getResidentReports(Auth::id());

        return $this->apiSuccess(MaintenanceRequestResource::collection($reports), 'Berhasil mengambil data laporan kerusakan.');
    }

    /**
     * Buat Laporan Kerusakan
     *
     * Membuat laporan kerusakan baru (sebagai penghuni).
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreMaintenanceRequest $request)
    {
        $validated = $request->validated();
        $images = $request->file('images') ?? [];

        $report = $this->maintenanceService->createReport(Auth::id(), $validated, $images);

        return $this->apiSuccess(new MaintenanceRequestResource($report), 'Laporan kerusakan berhasil dibuat.', 201);
    }

    /**
     * Detail Laporan Kerusakan
     *
     * Melihat detail dari sebuah laporan kerusakan beserta progress pekerjaannya.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $report = $this->maintenanceService->getReportById($id);

        // Verifikasi kepemilikan via reporter_user_id (data baru) atau relasi resident (data lama)
        $ownerId = $report->reporter_user_id ?? $report->resident?->user_id;
        if ($ownerId !== Auth::id()) {
            return $this->apiError('Anda tidak memiliki akses ke laporan ini.', 403);
        }

        return $this->apiSuccess(new MaintenanceRequestResource($report), 'Berhasil mengambil detail laporan.');
    }
}
