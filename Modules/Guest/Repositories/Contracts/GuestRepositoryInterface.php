<?php

namespace Modules\Guest\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Guest\Models\Guest;

interface GuestRepositoryInterface
{
    public function getByLeaseId(int $leaseId): Collection;

    public function getAllPaginated(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Guest;

    public function create(array $data): Guest;

    public function delete(Guest $guest): void;
}
