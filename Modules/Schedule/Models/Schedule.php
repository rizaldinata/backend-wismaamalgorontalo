<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Room\Models\Room;
use Modules\Schedule\Enums\SchedulePaymentScheme;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;

class Schedule extends Model
{
    protected $table = 'room_schedules';

    protected $guarded = ['id'];

    protected $casts = [
        'type' => ScheduleType::class,
        'status' => ScheduleStatus::class,
        'payment_scheme' => SchedulePaymentScheme::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'agreed_price' => 'decimal:2',
        'dp_amount' => 'decimal:2',
        'dp_paid_at' => 'datetime',
        'activated_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'tenant_user_id');
    }

    // Relasi ke modul Finance (invoices) dihapus karena melanggar batasan arsitektur modul
}
