<?php

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\Inventory;

interface InventoryRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(int $id): ?Inventory;
    public function create(array $data): Inventory;
    public function update(Inventory $inventory, array $data): Inventory;
    public function delete(Inventory $inventory): bool;
}
