<?php

namespace Modules\Rental\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Room\Models\Room;
use Modules\Finance\Models\Invoice;

class Lease extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Mengambil tagihan terbaru untuk mengecek status lunas/belum lunas
    public function latestInvoice()
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }
}