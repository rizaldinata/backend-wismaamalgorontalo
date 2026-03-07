<?php

namespace Modules\Rental\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resident_id' => $this->resident_id,
            'room_id' => $this->room_id,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'rental_type' => $this->rental_type,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

            'room' => $this->whenLoaded('room'),
            'resident' => $this->whenLoaded('resident'),
        ];
    }
}
