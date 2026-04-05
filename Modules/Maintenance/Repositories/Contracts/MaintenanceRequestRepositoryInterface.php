<?php

namespace Modules\Maintenance\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Models\MaintenanceRequestUpdate;

interface MaintenanceRequestRepositoryInterface
{
    public function createRequest(array $data): MaintenanceRequest;
    public function addRequestImages(MaintenanceRequest $request, array $imagePaths): void;
    public function getByResidentId(int $residentId): Collection;
    public function getAll(): Collection;
    public function findById(int $id): ?MaintenanceRequest;
    public function addUpdate(MaintenanceRequest $request, array $data): MaintenanceRequestUpdate;
    public function addUpdateImages(MaintenanceRequestUpdate $update, array $imagePaths): void;
    public function updateStatus(MaintenanceRequest $request, string $status): bool;
}
