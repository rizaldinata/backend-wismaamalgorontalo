<?php

namespace Modules\Rental\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Rental\database\factories\LeaseFactory;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Enums\RentalType;
use Modules\Resident\Models\Resident;
use Modules\Room\Models\Room;

class Lease extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'resident_id',
        'room_id',
        'start_date',
        'end_date',
        'finished_at',
        'rental_type',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'finished_at' => 'datetime',
        'status' => LeaseStatus::class,
        'rental_type' => RentalType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    protected static function newFactory()
    {
        return LeaseFactory::new();
    }
}
