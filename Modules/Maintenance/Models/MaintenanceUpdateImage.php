<?php

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceUpdateImage extends Model
{
    protected $fillable = [
        'maintenance_request_update_id',
        'image_path',
    ];

    public function requestUpdate(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequestUpdate::class, 'maintenance_request_update_id');
    }
}
