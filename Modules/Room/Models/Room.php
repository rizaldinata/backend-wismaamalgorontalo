<?php

namespace Modules\Room\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Room\database\factories\RoomFactory;
use Modules\Room\Enums\RoomStatus;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Models\Schedule;

class Room extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => RoomStatus::class,
        'facilities' => 'array'
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function activeSchedule()
    {
        return $this->hasOne(Schedule::class)->where('status', ScheduleStatus::ACTIVE->value);
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class)->orderBy('order');
    }

    protected static function newFactory()
    {
        return RoomFactory::new();
    }
}
