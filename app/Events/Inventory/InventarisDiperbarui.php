<?php

namespace App\Events\Inventory;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventarisDiperbarui
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $inventoryId,
        public readonly string $name,
        public readonly float $purchasePrice,
    ) {}
}
