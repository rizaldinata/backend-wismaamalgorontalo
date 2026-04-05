<?php

namespace Modules\Maintenance\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Models\MaintenanceRequestUpdate;
use Modules\Maintenance\Repositories\Contracts\MaintenanceRequestRepositoryInterface;

class MaintenanceRequestRepository implements MaintenanceRequestRepositoryInterface
{
    public function createRequest(array $data): MaintenanceRequest
    {
        return MaintenanceRequest::create($data);
    }

    public function addRequestImages(MaintenanceRequest $request, array $imagePaths): void
    {
        $images = array_map(fn($path) => ['image_path' => $path], $imagePaths);
        $request->images()->createMany($images);
    }

    public function getByResidentId(int $residentId): Collection
    {
        return MaintenanceRequest::with(['images', 'updates.user', 'updates.images'])
            ->where('resident_id', $residentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAll(): Collection
    {
        return MaintenanceRequest::with(['resident.user', 'room', 'images', 'updates.user', 'updates.images'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(int $id): ?MaintenanceRequest
    {
        return MaintenanceRequest::with(['resident.user', 'room', 'images', 'updates.user', 'updates.images'])
            ->findOrFail($id);
    }

    public function addUpdate(MaintenanceRequest $request, array $data): MaintenanceRequestUpdate
    {
        return $request->updates()->create($data);
    }

    public function addUpdateImages(MaintenanceRequestUpdate $update, array $imagePaths): void
    {
        $images = array_map(fn($path) => ['image_path' => $path], $imagePaths);
        $update->images()->createMany($images);
    }

    public function updateStatus(MaintenanceRequest $request, string $status): bool
    {
        return $request->update(['status' => $status]);
    }
}
