<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Finance\Enums\FineStatus;

class Fine extends Model
{
    protected $fillable = [
        'tenant_user_id',
        'schedule_id',
        'amount',
        'reason',
        'status',
        'waive_reason',
        'paid_at',
    ];

    protected $casts = [
        'status'  => FineStatus::class,
        'paid_at' => 'datetime',
        'amount'  => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'fine_invoice');
    }
}
