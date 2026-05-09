<?php

namespace Modules\Guest\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Guest\Models\Guest;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;

class GuestRepository implements GuestRepositoryInterface
{
    public function getByLeaseId(int $leaseId): Collection
    {
        return Guest::where('lease_id', $leaseId)
            ->with('bill')
            ->orderByDesc('check_in_at')
            ->get();
    }

    public function getAllPaginated(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $search  = $filters['search'] ?? null;

        $query = Guest::with(['lease.resident.user', 'lease.room'])
            ->orderByDesc('check_in_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('lease.resident.user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('lease.room', function ($r) use ($search) {
                      $r->where('number', 'like', "%{$search}%");
                  });
            });
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Guest
    {
        return Guest::find($id);
    }

    public function create(array $data): Guest
    {
        return Guest::create($data);
    }

    public function delete(Guest $guest): void
    {
        $guest->delete();
    }
}
