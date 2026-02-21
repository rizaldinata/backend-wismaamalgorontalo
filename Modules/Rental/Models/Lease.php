<?php

namespace Modules\Rental\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Resident\Models\Resident;
use Modules\Room\Models\Room;

class Lease extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'start_date',
        'end_date',
        'status',
        'total_price',
        'price_per_month',
        'payment_proof',
    ];

    protected $casts = [
        'status' => LeaseStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
