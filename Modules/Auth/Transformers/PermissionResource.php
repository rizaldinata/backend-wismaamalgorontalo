<?php

namespace Modules\Auth\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $requst): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target' => $this->target,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
