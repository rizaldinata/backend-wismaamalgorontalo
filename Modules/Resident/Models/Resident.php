<?php

namespace Modules\Resident\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Resident\database\factories\ResidentFactory;

class Resident extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leases()
    {
        return $this->hasMany(\Modules\Rental\Models\Lease::class);
    }

    public function getActiveLeaseAttribute()
    {
        return $this->leases()->where('status', \Modules\Rental\Enums\LeaseStatus::ACTIVE)->first();
    }

    protected static function newFactory()
    {
        return ResidentFactory::new();
    }
}
