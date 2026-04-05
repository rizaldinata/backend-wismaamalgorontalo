<?php

use Modules\Finance\Models\Expense;
use Modules\Finance\Repositories\Contracts\ExpenseRepositoryInterface;
use Modules\Finance\Services\ExpenseService;

test('gagal memperbarui pengeluaran yang terintegrasi modul lain', function () {
    $expense = new Expense([
        'reference_type' => 'Modules\Inventory\Models\Stock',
        'reference_id' => 99
    ]);

    $mockRepo = \Mockery::mock(ExpenseRepositoryInterface::class);
    $service = new ExpenseService($mockRepo);

    expect(fn() => $service->updateManualExpense($expense, ['amount' => 50000]))
        ->toThrow(DomainException::class, 'Pengeluaran ini terintegrasi dengan modul lain');
});
