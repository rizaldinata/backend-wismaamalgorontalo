<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\Expense;

interface ExpenseRepositoryInterface
{
    public function create(array $data): Expense;
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;
    public function findByReference(int $refId, string $refType): ?Expense;
    public function update(Expense $expense, array $data): Expense;
    public function delete(Expense $expense): bool;
    public function findById(int $id): ?Expense;
    public function findOrFail(int $id): ?Expense;
    public function getTotalByPeriod(?int $month = null, ?int $year = null): float;
}
