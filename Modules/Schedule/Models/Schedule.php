<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Room\Models\Room;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;

class Schedule extends Model
{
    protected $table = 'room_schedules';

    protected $guarded = ['id'];

    protected $casts = [
        'type'         => ScheduleType::class,
        'status'       => ScheduleStatus::class,
        'start_date'   => 'date',
        'end_date'     => 'date',
        'agreed_price' => 'decimal:2',
        'activated_at' => 'datetime',
        'finished_at'  => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
