<?php

namespace Modules\Schedule\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'room_id'          => $this->room_id,
            'type'             => $this->type?->value,
            'status'           => $this->status?->value,
            'start_date'       => $this->start_date?->toDateString(),
            'end_date'         => $this->end_date?->toDateString(),
            'agreed_price'     => $this->agreed_price,
            'tenant' => [
                'user_id'    => $this->tenant_user_id,
                'name'       => $this->tenant_name,
                'id_number'  => $this->tenant_id_number,
                'phone'      => $this->tenant_phone,
                'id_photo'   => $this->tenant_id_photo,
            ],
            'activated_at'     => $this->activated_at?->toDateTimeString(),
            'finished_at'      => $this->finished_at?->toDateTimeString(),
            'created_at'       => $this->created_at?->toDateTimeString(),
        ];
    }
}
