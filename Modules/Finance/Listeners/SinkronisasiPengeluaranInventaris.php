<?php

namespace Modules\Finance\Listeners;

use App\Events\Inventory\InventarisDiperbarui;
use Modules\Finance\Services\ExpenseService;

class SinkronisasiPengeluaranInventaris
{
    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {}

    public function handle(InventarisDiperbarui $event): void
    {
        $this->expenseService->syncExpenseByReference(
            $event->inventoryId,
            'Modules\\Inventory\\Models\\Inventory',
            [
                'title' => "Revisi Pembelian: {$event->name}",
                'amount' => $event->purchasePrice,
            ]
        );
    }
}
