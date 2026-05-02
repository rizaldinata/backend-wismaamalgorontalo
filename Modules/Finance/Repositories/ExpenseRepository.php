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

    public function findOrFail(int $id): ?Expense
    {
        return Expense::findOrFail($id);
    }

    public function getTotalByPeriod(?int $month = null, ?int $year = null): float
    {
        $query = Expense::query();

        if ($year !== null) {
            $query->whereYear('expense_date', $year);
        }

        if ($month !== null) {
            $query->whereMonth('expense_date', $month);
        }

        return (float) $query->sum('amount');
    }
}
