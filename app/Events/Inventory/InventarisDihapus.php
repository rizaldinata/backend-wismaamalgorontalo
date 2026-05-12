<?php

namespace App\Events\Inventory;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventarisDihapus
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $inventoryId,
    ) {}
}
