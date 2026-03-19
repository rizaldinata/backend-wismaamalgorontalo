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
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
