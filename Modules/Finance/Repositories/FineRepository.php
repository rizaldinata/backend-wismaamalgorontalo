<?php

namespace Modules\Finance\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Models\Fine;
use Modules\Finance\Repositories\Contracts\FineRepositoryInterface;

class FineRepository implements FineRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Fine::orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['tenant_user_id'])) {
            $query->where('tenant_user_id', $filters['tenant_user_id']);
        }

        return $query->paginate($perPage);
    }

    public function getByUser(int $userId, array $filters = []): Collection
    {
        $query = Fine::where('tenant_user_id', $userId)->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function findById(int $id): ?Fine
    {
        return Fine::find($id);
    }

    public function create(array $data): Fine
    {
        return Fine::create($data);
    }

    public function update(Fine $fine, array $data): Fine
    {
        $fine->update($data);

        return $fine->fresh();
    }

    public function findManyByIds(array $ids): Collection
    {
        return Fine::whereIn('id', $ids)->get();
    }
}
