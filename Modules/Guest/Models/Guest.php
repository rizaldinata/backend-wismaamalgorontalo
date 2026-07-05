<?php

namespace Modules\Guest\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Guest\Enums\GuestRelationship;

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
        'stay_completed_notified_at',
        'relationship',
        'identity_image_path',
        'total_days',
        'billable_days',
        'charge_amount',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'relationship' => GuestRelationship::class,
        'charge_amount' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->hasOne(GuestBill::class)->latestOfMany();
    }

    public function bills()
    {
        return $this->hasMany(GuestBill::class);
    }

    public function getIdentityImageUrlAttribute(): ?string
    {
        if (! $this->identity_image_path) {
            return null;
        }

        return url('/storage/'.$this->identity_image_path);
    }

    // Removed cross-module eloquent relationship (schedule)
}
