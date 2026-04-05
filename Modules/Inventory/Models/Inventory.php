<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\ItemCondition;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'quantity',
        'condition',
        'purchase_price',
    ];

    protected $cast = [
        'condition' => ItemCondition::class,
        'purchase_price' => 'decimal:2',
    ];
}
