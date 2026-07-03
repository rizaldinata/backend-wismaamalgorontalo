<?php

namespace Modules\Schedule\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Schedule\Enums\ScheduleStatus;

class ScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'agreed_price' => $this->agreed_price,
            'tenant' => [
                'user_id' => $this->tenant_user_id,
                'name' => $this->tenant_name,
                'id_number' => $this->tenant_id_number,
                'phone' => $this->tenant_phone,
                'id_photo' => $this->tenant_id_photo,
            ],
            'room' => $this->whenLoaded('room', fn () => [
                'id' => $this->room->id,
                'number' => $this->room->number,
                'title' => $this->room->title,
                'price' => $this->room->price,
            ]),
            'payment_scheme' => $this->payment_scheme?->value,
            'dp_amount' => $this->dp_amount !== null ? (float) $this->dp_amount : null,
            'dp_paid_at' => $this->dp_paid_at?->toDateTimeString(),
            'dp_refund_eligible' => in_array($this->status, [ScheduleStatus::DP_TERBAYAR, ScheduleStatus::TERKONFIRMASI])
                && $this->payment_scheme?->value === 'dp'
                    ? Carbon::now()->lt(Carbon::parse($this->start_date)->subDays(3))
                    : null,
            'activated_at' => $this->activated_at?->toDateTimeString(),
            'finished_at' => $this->finished_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
