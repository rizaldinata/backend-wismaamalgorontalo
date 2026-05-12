<?php

namespace Modules\Finance\Listeners;

use App\Events\Inventory\InventarisDihapus;
use Modules\Finance\Services\ExpenseService;

class HapusPengeluaranInventaris
{
    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {}

    public function handle(InventarisDihapus $event): void
    {
        $this->expenseService->removeExpenseByReference(
            $event->inventoryId,
            \Modules\Inventory\Models\Inventory::class,
        );
    }
}
