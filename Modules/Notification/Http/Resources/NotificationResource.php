<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'target'  => $this->resource['target'],
            'status'  => $this->resource['status'],
            'message' => 'Notification dispatched successfully',
        ];
    }
}