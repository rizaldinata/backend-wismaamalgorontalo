<?php

namespace Modules\Rental\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Rental\Models\Lease;
use Modules\Rental\Repositories\Contracts\LeaseRepositoryInterface;

class LeaseRepository implements LeaseRepositoryInterface
{
    public function getAllPaginated(array $filters = []): LengthAwarePaginator
    {
        return Lease::with(['room', 'resident.user'])
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(15);
    }

    public function findById(int $id): ?Lease
    {
        return Lease::with(['room', 'resident.user'])->findOrFail($id);
    }

    public function getByResidentId(int $residentId): Collection
    {
        return Lease::with('room')->where('resident_id', $residentId)->latest()->get();
    }

    public function create(array $data): Lease
    {
        return Lease::create($data);
    }

    public function updateStatus(Lease $lease, string $status): Lease
    {
        $lease->update(['status' => $status]);
        return $lease;
    }
}
