<?php

namespace Modules\Inventory\Services;

use App\Events\Inventory\InventariBaru;
use App\Events\Inventory\InventarisDihapus;
use App\Events\Inventory\InventarisDiperbarui;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Repositories\Contracts\InventoryRepositoryInterface;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
    ) {}

    public function createInventory(array $data): Inventory
    {
        return DB::transaction(function () use ($data) {
            $inventory = $this->inventoryRepository->create($data);

            if (! empty($data['purchase_price']) && $data['purchase_price'] > 0) {
                InventariBaru::dispatch(
                    $inventory->id,
                    $inventory->name,
                    $inventory->quantity,
                    (float) $data['purchase_price'],
                );
            }

            return $inventory;
        });
    }

    public function updateInventory(Inventory $inventory, array $data): Inventory
    {
        return DB::transaction(function () use ($inventory, $data) {
            $updatedInventory = $this->inventoryRepository->update($inventory, $data);

            if (array_key_exists('purchase_price', $data)) {
                InventarisDiperbarui::dispatch(
                    $inventory->id,
                    $updatedInventory->name,
                    (float) ($data['purchase_price'] ?? 0),
                );
            }

            return $updatedInventory;
        });
    }

    public function deleteInventory(Inventory $inventory): bool
    {
        return DB::transaction(function () use ($inventory) {
            $id = $inventory->id;
            $deleted = $this->inventoryRepository->delete($inventory);

            if ($deleted) {
                InventarisDihapus::dispatch($id);
            }

            return $deleted;
        });
    }
}
