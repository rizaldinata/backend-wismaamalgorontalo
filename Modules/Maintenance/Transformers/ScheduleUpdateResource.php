<?php

namespace Modules\Maintenance\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleUpdateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->maintenance_schedule_id,
            'user_name' => $this->user->name,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
