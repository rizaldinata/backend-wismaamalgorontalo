<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\database\factories\InvoiceFactory;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Rental\Models\Lease;
use Modules\Schedule\Models\Schedule;

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
    ];

    protected $casts = [
        'due_date' => 'date',
        'status' => InvoiceStatus::class,
    ];

    public function lease()
    {
        return $this->belongsTo(Lease::class, 'lease_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public static function newFactory()
    {
        return InvoiceFactory::new();
    }
}
