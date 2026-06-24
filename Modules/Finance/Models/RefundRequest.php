<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Schedule\Models\Schedule;

class RefundRequest extends Model
{
    protected $fillable = [
        'schedule_id',
        'payment_id',
        'bank_name',
        'account_number',
        'account_holder_name',
        'refund_amount',
        'admin_fee',
        'proof_path',
        'status',
        'is_refund_eligible',
        'admin_notes',
        'processed_at',
    ];

    protected $casts = [
        'refund_amount'      => 'decimal:2',
        'admin_fee'          => 'decimal:2',
        'is_refund_eligible' => 'boolean',
        'processed_at'       => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_path ? url('/storage/'.$this->proof_path) : null;
    }
}
