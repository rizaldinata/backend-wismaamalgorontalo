<?php

namespace Modules\Finance\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\Expense;
use Modules\Finance\Repositories\Contracts\ExpenseRepositoryInterface;

class ExpenseRepository implements ExpenseRepositoryInterface
{
    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Expense::latest('expense_date')->paginate($perPage);
    }
}
