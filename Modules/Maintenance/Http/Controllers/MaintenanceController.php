<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Maintenance\Http\Requests\StoreMaintenanceRequest;
use Modules\Maintenance\Services\MaintenanceService;
use Modules\Maintenance\Transformers\MaintenanceRequestResource;

class MaintenanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MaintenanceService $maintenanceService
    ) {}

    public function myReports()
    {
        $reports = $this->maintenanceService->getResidentReports(Auth::id());
        return $this->apiSuccess(MaintenanceRequestResource::collection($reports), 'Berhasil mengambil data laporan kerusakan.');
    }

    public function store(StoreMaintenanceRequest $request)
    {
        $validated = $request->validated();
        $images = $request->file('images') ?? [];

        $report = $this->maintenanceService->createReport(Auth::id(), $validated, $images);
        
        return $this->apiSuccess(new MaintenanceRequestResource($report), 'Laporan kerusakan berhasil dibuat.', 201);
    }

    public function show($id)
    {
        $report = $this->maintenanceService->getReportById($id);
        
        // Prevent seeing other resident's report
        if ($report->resident->user_id !== Auth::id()) {
            return $this->apiError('Anda tidak memiliki akses ke laporan ini.', 403);
        }

        return $this->apiSuccess(new MaintenanceRequestResource($report), 'Berhasil mengambil detail laporan.');
    }
}
