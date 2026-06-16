<?php

namespace Modules\Finance\Listeners;

use App\Events\Inventory\InventariBaru;
use Modules\Finance\Services\ExpenseService;

class CatatPengeluaranInventariBaru
{
    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {}

    public function handle(InventariBaru $event): void
    {
        if ($event->purchasePrice <= 0) {
            return;
        }

        $this->expenseService->recordExpense([
            'title' => "Pembelian Barang: {$event->name}",
            'description' => "Pembelian {$event->quantity} unit {$event->name}",
            'amount' => $event->purchasePrice,
            'expense_date' => now(),
            'reference_id' => $event->inventoryId,
            'reference_type' => 'Modules\\Inventory\\Models\\Inventory',
        ]);
    }
}
