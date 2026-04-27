<?php

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Maintenance\Enums\ScheduleStatus;

class MaintenanceScheduleUpdate extends Model
{
    protected $fillable = [
        'maintenance_schedule_id',
        'user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => ScheduleStatus::class,
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
