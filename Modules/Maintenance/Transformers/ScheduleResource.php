<?php

namespace Modules\Maintenance\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'technician_name'  => $this->technician_name,
            'location'         => $this->location,
            'type'             => $this->type->value,
            'subtype'          => $this->subtype->value,
            'status'           => $this->status->value,
            'notes'            => $this->notes,
            'start_time'       => $this->start_time?->toIso8601String(),
            'end_time'         => $this->end_time?->toIso8601String(),
            'created_by'       => $this->created_by,
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
            'updates'          => ScheduleUpdateResource::collection($this->whenLoaded('updates')),
        ];
    }
}
