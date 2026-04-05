<?php

namespace Modules\Maintenance\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestUpdateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? 'Unknown',
            ],
            'status' => $this->status ? $this->status->value : null,
            'description' => $this->description,
            'images' => $this->images->map(fn($img) => Storage::url($img->image_path)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
