<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Maintenance\Http\Requests\StoreMaintenanceUpdate;
use Modules\Maintenance\Services\DamageReportService;
use Modules\Maintenance\Transformers\MaintenanceRequestResource;
use Modules\Maintenance\Transformers\MaintenanceRequestUpdateResource;

class AdminDamageReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DamageReportService $maintenanceService
    ) {}

    public function index()
    {
        $perPage = request()->get('per_page', 10);
        $reports = $this->maintenanceService->getPaginatedReports($perPage);
        $resource = MaintenanceRequestResource::collection($reports)->response()->getData(true);

        return $this->apiSuccess($resource['data'], 'Berhasil mengambil daftar keluhan.', 200, $resource['meta']);
    }

    public function show($id)
    {
        $report = $this->maintenanceService->getReportById($id);

        return $this->apiSuccess(new MaintenanceRequestResource($report), 'Detail laporan berhasil diambil.');
    }

    public function storeUpdate(StoreMaintenanceUpdate $request, $id)
    {
        $validated = $request->validated();
        $images = $request->file('images') ?? [];

        $update = $this->maintenanceService->addUpdate(Auth::id(), $id, $validated, $images);

        return $this->apiSuccess(new MaintenanceRequestUpdateResource($update), 'Progres/balasan berhasil ditambahkan.', 201);
    }
}
