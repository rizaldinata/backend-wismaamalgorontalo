<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\RefundRequest;

interface RefundRequestRepositoryInterface
{
    public function create(array $data): RefundRequest;

    public function findOrFail(int $id): RefundRequest;

    public function update(RefundRequest $refundRequest, array $data): RefundRequest;

    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function hasPendingBySchedule(int $scheduleId): bool;
}
