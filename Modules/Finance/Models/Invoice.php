<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\database\factories\InvoiceFactory;
use Modules\Finance\Enums\InvoiceStatus;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'schedule_id',
        'invoice_number',
        'amount',
        'status',
        'due_date',
        'tenant_user_id',
        'tenant_name',
        'tenant_phone',
        'room_number',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'status' => InvoiceStatus::class,
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public static function newFactory()
    {
        return InvoiceFactory::new();
    }
}
