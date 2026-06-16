<?php

namespace Modules\Guest\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGuestResource extends JsonResource
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
            'penghuni' => $this->lease?->resident?->user?->name ?? '-',
            'kamar' => $this->lease?->room?->number ?? '-',
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
