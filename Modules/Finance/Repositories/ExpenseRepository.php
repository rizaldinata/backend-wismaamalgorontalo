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

    public function findByReference(int $refId, string $refType): ?Expense
    {
        return Expense::where('reference_id', $refId)
            ->where('reference_type', $refType)
            ->first();
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense;
    }

    public function delete(Expense $expense): bool
    {
        return $expense->delete();
    }

    public function findById(int $id): ?Expense
    {
        return Expense::find($id);
    }
}
