<?php

namespace Modules\Guest\Models;

use Illuminate\Database\Eloquent\Model;

class GuestActiveContext extends Model
{
    protected $table = 'guest_active_contexts';

    protected $fillable = [
        'user_id',
        'lease_id',
        'schedule_id',
        'room_id',
        'room_price',
        'tenant_name',
        'tenant_email',
        'tenant_phone',
        'is_active',
    ];

    protected $casts = [
        'room_price' => 'decimal:2',
        'is_active'  => 'boolean',
    ];
}
