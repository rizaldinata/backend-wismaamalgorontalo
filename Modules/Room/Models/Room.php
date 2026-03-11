<?php

namespace Modules\Room\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Rental\Models\Lease;
use Modules\Room\Enums\RoomStatus;

class Room extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => RoomStatus::class,
        'facilities' => 'array'
    ];

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease()
    {
        return $this->hasOne(Lease::class)->where('status', 'active');
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class)->orderBy('order');
    }
}
