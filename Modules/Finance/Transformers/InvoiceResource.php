<?php

namespace Modules\Finance\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'amount' => (float) $this->amount,
            'status' => is_object($this->status) ? $this->status->value : $this->status,
            'due_date' => $this->due_date ? $this->due_date->format('Y-m-d') : null,
            'lease' => [
                'id' => $this->schedule_id ?? $this->lease_id,
                'resident_name' => $this->tenant_name,
                'room_number' => $this->room_number,
            ],
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
