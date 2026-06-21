<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Models\Fine;

interface FineRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function getByUser(int $userId, array $filters = []): Collection;

    public function findById(int $id): ?Fine;

    public function create(array $data): Fine;

    public function update(Fine $fine, array $data): Fine;

    public function findManyByIds(array $ids): Collection;
}
