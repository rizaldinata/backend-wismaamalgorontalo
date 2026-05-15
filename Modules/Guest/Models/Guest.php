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
        'user_id',
        'schedule_reference_id',
        'tenant_name',
        'tenant_email',
        'tenant_phone',
        'name',
        'check_in_at',
        'check_out_at',
        'relationship',
        'total_days',
        'billable_days',
        'charge_amount',
    ];

    protected $casts = [
        'check_in_at'    => 'datetime',
        'check_out_at'   => 'datetime',
        'relationship'   => GuestRelationship::class,
        'charge_amount'  => 'decimal:2',
    ];

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function bill()
    {
        return $this->hasOne(GuestBill::class);
    }
}
