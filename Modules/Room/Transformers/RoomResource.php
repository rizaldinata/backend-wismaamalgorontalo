<?php

namespace Modules\Room\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'number' => $this->number,
            'price' => $this->price,
            'price_daily' => $this->price_daily,
            'price_formatted' => 'Rp ' . number_format($this->price, 0, ',', '.'),
            'price_daily_formatted' => $this->price_daily ? 'Rp ' . number_format($this->price_daily, 0, ',', '.') : '-',
            'status' => $this->status->label(),
            'status_code' => $this->status->value,
            'description' => $this->description,
            'facilities' => $this->facilities ?? [],
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->image_url,
                        'thumbnail' => $image->thumbnail_url,
                        'order' => $image->order,
                    ];
                });
            }),
            'schedules' => $this->whenLoaded('schedules', fn () => $this->schedules->map(fn ($s) => [
                'id'         => $s->id,
                'type'       => is_object($s->type) ? $s->type->value : $s->type,
                'status'     => is_object($s->status) ? $s->status->value : $s->status,
                'start_date' => $s->start_date?->format('Y-m-d'),
                'end_date'   => $s->end_date?->format('Y-m-d'),
                'tenant'     => $s->tenant_name,
            ])),
        ];
    }
}
