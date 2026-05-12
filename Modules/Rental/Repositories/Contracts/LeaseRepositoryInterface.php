<?php

namespace Modules\Rental\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Rental\Models\Lease;

interface LeaseRepositoryInterface
{
    public function getAllPaginated(array $filters = []): LengthAwarePaginator;
    public function findById(int $id): ?Lease;
    public function getByResidentId(int $residentId): Collection;
    public function create(array $data): Lease;
    public function updateStatus(Lease $lease, string $status): Lease;
}
