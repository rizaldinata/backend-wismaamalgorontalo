<?php

namespace Modules\Guest\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'check_in_at' => $this->check_in_at?->toDateTimeString(),
            'check_out_at' => $this->check_out_at?->toDateTimeString(),
            'relationship' => $this->relationship?->value,
            'relationship_label' => $this->relationship?->label(),
            'total_days' => $this->total_days,
            'billable_days' => $this->billable_days,
            'charge_amount' => (float) $this->charge_amount,
            'bill' => $this->bill ? new GuestBillResource($this->bill) : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
