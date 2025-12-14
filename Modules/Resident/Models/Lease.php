<?php

namespace Modules\Resident\Models;

use App\Models\User;
use Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lease extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id',
        'room_id',
        'start_date',
        'end_date',
        'status',
        'total_price',
    ];

    protected $casts = [
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
