<?php

namespace Modules\Inventory\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'condition' => $this->condition->value,
            'purchase_price' => $this->purchase_price ? (float) $this->purchase_price : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
