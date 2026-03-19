<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\Expense;

interface ExpenseRepositoryInterface
{
    public function create(array $data): Expense;
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;
}
