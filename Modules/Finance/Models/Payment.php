<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Finance\database\factories\PaymentFactory;
use Modules\Finance\Enums\PaymentStatus;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'payment_method',
        'payment_proof_path',
        'transaction_id',
        'status',
        'admin_notes',
        'snap_token',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getPaymentProofUrlAttribute()
    {
        return $this->payment_proof_path ? url('/storage/' . $this->payment_proof_path) : null;
    }

    public static function newFactory()
    {
        return PaymentFactory::new();
    }
}
