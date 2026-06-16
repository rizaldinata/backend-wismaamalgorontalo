<?php

namespace Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Maintenance\Enums\ScheduleStatus;
use Modules\Maintenance\Enums\ScheduleSubtype;
use Modules\Maintenance\Enums\ScheduleType;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $table = 'maintenance_schedules';

    protected $fillable = [
        'technician_name',
        'location',
        'type',
        'subtype',
        'status',
        'notes',
        'start_time',
        'end_time',
        'created_by',
    ];

    protected $casts = [
        'type' => ScheduleType::class,
        'subtype' => ScheduleSubtype::class,
        'status' => ScheduleStatus::class,
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function updates(): HasMany
    {
        return $this->hasMany(MaintenanceScheduleUpdate::class, 'maintenance_schedule_id');
    }
}
