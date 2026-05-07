<?php

namespace Modules\Guest\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Guest\Enums\GuestRelationship;
use Modules\Rental\Models\Lease;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'name',
        'check_in_at',
        'check_out_at',
        'relationship',
    ];

    protected $casts = [
        'check_in_at'  => 'datetime',
        'check_out_at' => 'datetime',
        'relationship' => GuestRelationship::class,
    ];

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }
}
