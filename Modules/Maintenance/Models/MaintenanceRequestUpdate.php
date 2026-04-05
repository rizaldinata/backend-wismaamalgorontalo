<?php

namespace Modules\Maintenance\Models;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Maintenance\Enums\MaintenanceStatus;

class MaintenanceRequestUpdate extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'user_id',
        'status',
        'description',
    ];

    protected $casts = [
        'status' => MaintenanceStatus::class,
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(MaintenanceUpdateImage::class, 'maintenance_request_update_id');
    }
}
