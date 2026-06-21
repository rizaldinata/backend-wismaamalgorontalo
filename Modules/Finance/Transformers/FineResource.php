<?php

namespace Modules\Finance\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class FineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'tenant_user_id' => $this->tenant_user_id,
            'schedule_id'    => $this->schedule_id,
            'tenant_name'    => $this->tenant?->name,
            'amount'         => (float) $this->amount,
            'reason'         => $this->reason,
            'status'         => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'waive_reason'   => $this->waive_reason,
            'paid_at'        => $this->paid_at?->toIso8601String(),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
