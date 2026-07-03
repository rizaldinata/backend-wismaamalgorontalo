<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\database\factories\FixedExpenseEntryFactory;
use Modules\Finance\Enums\JenisPengeluaranTetap;

class FixedExpenseEntry extends Model
{
    use HasFactory;

    protected $table = 'fixed_expense_entries';

    protected $fillable = [
        'jenis',
        'bulan',
        'tahun',
        'amount',
        'notes',
        'recorded_by',
        'is_filled',
    ];

    protected $casts = [
        'jenis' => JenisPengeluaranTetap::class,
        'amount' => 'decimal:2',
        'bulan' => 'integer',
        'tahun' => 'integer',
        'is_filled' => 'boolean',
    ];

    protected static function newFactory(): FixedExpenseEntryFactory
    {
        return FixedExpenseEntryFactory::new();
    }
}
