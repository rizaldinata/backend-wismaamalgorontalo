<?php

namespace Modules\Maintenance\Models;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Maintenance\Enums\MaintenanceStatus;
use Modules\Resident\Models\Resident;
use Modules\Room\Models\Room;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'resident_id',
        'room_id',
        'title',
        'description',
        'status',
        'reported_at',
    ];

    protected $casts = [
        'status' => MaintenanceStatus::class,
        'reported_at' => 'datetime',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(MaintenanceRequestImage::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(MaintenanceRequestUpdate::class)->orderBy('created_at', 'desc');
    }
}
