<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\database\factories\InventoryFactory;
use Modules\Inventory\Enums\ItemCondition;

class Inventory extends Model
{
    use HasFactory;

    protected static function newFactory(): InventoryFactory
    {
        return InventoryFactory::new();
    }

    protected $fillable = [
        'name',
        'description',
        'quantity',
        'condition',
        'purchase_price',
    ];

    protected $casts = [
        'condition' => ItemCondition::class,
        'purchase_price' => 'decimal:2',
    ];
}
