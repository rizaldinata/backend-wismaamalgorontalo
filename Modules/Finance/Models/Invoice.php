<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\database\factories\InvoiceFactory;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Schedule\Models\Schedule;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'schedule_id',
        'type',
        'invoice_number',
        'amount',
        'status',
        'due_date',
        'payment_expires_at',
        'tenant_user_id',
        'tenant_name',
        'tenant_phone',
        'room_number',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_expires_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'status' => InvoiceStatus::class,
        'type' => InvoiceType::class,
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Relasi schedule() telah dihapus untuk mematuhi arsitektur Direct Service Access

    public function fines()
    {
        return $this->belongsToMany(Fine::class, 'fine_invoice');
    }

    public static function newFactory()
    {
        return InvoiceFactory::new();
    }
}
