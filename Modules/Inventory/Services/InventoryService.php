<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Repositories\Contracts\InventoryRepositoryInterface;
use Modules\Finance\Services\ExpenseService;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly ExpenseService $expenseService
    ) {}

    public function createInventory(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            $inventory = $this->inventoryRepository->create($data);

            if (!empty($data['purchase_price']) && $data['purchase_price'] > 0) {
                $this->expenseService->recordExpense([
                    'title' => "Pembelian Barang: {$inventory->name}",
                    'description' => "Pembelian {$inventory->quantity} unit {$inventory->name}",
                    'amount' => $data['purchase_price'],
                    'expense_date' => now(),
                    'reference_id' => $inventory->id,
                    'reference_type' => Inventory::class,
                ]);
            }

            return $inventory;
        });
    }

    public function updateInventory(Inventory $inventory, array $data): Inventory
    {
        return DB::transaction(function () use ($inventory, $data) {
            $updatedInventory = $this->inventoryRepository->update($inventory, $data);

            if (array_key_exists('purchase_price', $data)) {
                $this->expenseService->syncExpenseByReference(
                    $inventory->id,
                    Inventory::class,
                    [
                        'title' => "Revisi Pembelian: {$updatedInventory->name}",
                        'amount' => $data['purchase_price']
                    ]
                );
            }

            return $updatedInventory;
        });
    }

    public function deleteInventory(Inventory $inventory): bool
    {
        return DB::transaction(function () use ($inventory) {
            $this->expenseService->removeExpenseByReference($inventory->id, Inventory::class);

            return $this->inventoryRepository->delete($inventory);
        });
    }
}
