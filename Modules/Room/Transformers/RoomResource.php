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
            'number' => $this->number,
            'type' => $this->type,
            'price' => $this->price,
            'price_formatted' => 'Rp ' . number_format($this->price, 0, ',', '.'),
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
        ];
    }
}
