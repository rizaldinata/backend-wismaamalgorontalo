<?php

namespace Modules\Guest\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Guest\Enums\GuestBillStatus;

class GuestBill extends Model
{
    use HasFactory;

    protected $table = 'guest_bills';

    protected $fillable = [
        'guest_id',
        'bill_number',
        'amount',
        'payment_method',
        'status',
        'payment_proof_path',
        'transaction_id',
        'snap_token',
        'admin_notes',
        'paid_at',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'status'   => GuestBillStatus::class,
        'paid_at'  => 'datetime',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (!$this->payment_proof_path) {
            return null;
        }

        return url('/storage/' . $this->payment_proof_path);
    }
}
