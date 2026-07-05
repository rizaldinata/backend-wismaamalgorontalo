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

    public function getByScheduleId(int $scheduleId): Collection
    {
        return Guest::where('schedule_reference_id', $scheduleId)
            ->with('bill')
            ->orderByDesc('check_in_at')
            ->get();
    }

    public function getAllPaginated(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;

        $query = Guest::with(['bill'])
            ->orderByDesc('check_in_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tenant_name', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->whereNull('stay_completed_notified_at');
        } elseif ($status === 'completed') {
            $query->whereNotNull('stay_completed_notified_at');
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

    public function update(Guest $guest, array $data): Guest
    {
        $guest->update($data);

        return $guest;
    }

    public function delete(Guest $guest): void
    {
        $guest->delete();
    }
}
