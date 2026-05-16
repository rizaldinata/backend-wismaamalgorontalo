<?php

namespace Modules\Maintenance\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'resident' => [
                'id' => $this->reporter_user_id ?? $this->resident_id,
                'name' => $this->reporter_name ?? $this->resident?->user?->name ?? 'Unknown',
                'phone' => $this->reporter_phone ?? $this->resident?->phone_number ?? null,
            ],
            'room' => $this->room ? [
                'id' => $this->room->id,
                'number' => $this->room->number,
            ] : null,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status ? $this->status->value : null,
            'reported_at' => $this->reported_at ? $this->reported_at->toIso8601String() : null,
            'images' => $this->images ? $this->images->map(fn ($img) => url("api/maintenance/media/{$img->image_path}")) : [],
            'timeline' => MaintenanceRequestUpdateResource::collection($this->whenLoaded('updates')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
