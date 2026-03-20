<?php

namespace Modules\Finance\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'expense_date' => $this->expense_date->format('Y-m-d'),

            'is_integrated' => $this->reference_type !== null,
            'source' => $this->reference_type !== null ? 'Inventory / Sistem' : 'Manual Finance',

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
