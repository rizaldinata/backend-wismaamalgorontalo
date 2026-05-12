<?php

namespace Modules\Finance\Services;

use DomainException;
use Modules\Finance\Models\Expense;
use Modules\Finance\Repositories\Contracts\ExpenseRepositoryInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenseRepository
    ) {}

    public function getAllExpenses(int $perPage = 15)
    {
        return $this->expenseRepository->getPaginated($perPage);
    }

    public function createManualExpense(array $data): Expense
    {
        $data['reference_id'] = null;
        $data['reference_type'] = null;
        $data['expense_date'] = $data['expense_date'] ?? now()->toDateString();

        return $this->expenseRepository->create($data);
    }

    public function updateManualExpense(Expense $expense, array $data): Expense
    {
        if ($expense->reference_type !== null) {
            throw new DomainException('Pengeluaran ini terintegrasi dengan modul lain (Inventory). Silakan edit dari data barang terkait.');
        }

        return $this->expenseRepository->update($expense, $data);
    }

    public function deleteManualExpense(Expense $expense): bool
    {
        if ($expense->reference_type !== null) {
            throw new DomainException('Pengeluaran ini terintegrasi dengan modul lain (Inventory). Silakan hapus barang terkait jika ingin membatalkan pengeluaran.');
        }

        return $this->expenseRepository->delete($expense);
    }

    public function recordExpense(array $data)
    {
        $data['expense_date'] = $data['expense_date'] ?? now()->toDateString();

        return $this->expenseRepository->create($data);
    }

    public function removeExpenseByReference(int $refId, string $refType): bool
    {
        $expense = $this->expenseRepository->findByReference($refId, $refType);
        if ($expense) {
            return $this->expenseRepository->delete($expense);
        }
        return false;
    }

    public function syncExpenseByReference(int $refId, string $refType, array $data)
    {
        $expense = $this->expenseRepository->findByReference($refId, $refType);

        if ($expense) {
            if (empty($data['amount']) || $data['amount'] <= 0) {
                return $this->expenseRepository->delete($expense);
            }
            return $this->expenseRepository->update($expense, $data);
        } else {
            if (!empty($data['amount']) && $data['amount'] > 0) {
                $data['reference_id'] = $refId;
                $data['reference_type'] = $refType;
                return $this->recordExpense($data);
            }
        }

        return null;
    }
}
