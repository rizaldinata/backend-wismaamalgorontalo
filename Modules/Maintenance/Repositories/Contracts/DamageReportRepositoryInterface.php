<?php

namespace Modules\Maintenance\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Models\MaintenanceRequestUpdate;

interface DamageReportRepositoryInterface
{
    public function createRequest(array $data): \Modules\Maintenance\Models\MaintenanceRequest;
    public function getByResidentId(int $residentId);
    public function getAll();
    public function findById(int $id);
    public function addRequestImages(\Modules\Maintenance\Models\MaintenanceRequest $request, array $imagePaths);
    public function addUpdate(\Modules\Maintenance\Models\MaintenanceRequest $request, array $data): \Modules\Maintenance\Models\MaintenanceRequestUpdate;
    public function addUpdateImages(\Modules\Maintenance\Models\MaintenanceRequestUpdate $update, array $imagePaths);
    public function updateStatus(\Modules\Maintenance\Models\MaintenanceRequest $request, string $status): bool;
}
