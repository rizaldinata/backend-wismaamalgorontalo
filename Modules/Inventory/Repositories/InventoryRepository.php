<?php

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Repositories\Contracts\InventoryRepositoryInterface;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function getAll(): Collection
    {
        return Inventory::latest()->get();
    }

    public function findById(int $id): ?Inventory
    {
        return Inventory::findOrFail($id);
    }

    public function create(array $data): Inventory
    {
        return Inventory::create($data);
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update($data);
        return $inventory;
    }

    public function delete(Inventory $inventory): bool
    {
        return $inventory->delete();
    }
}
