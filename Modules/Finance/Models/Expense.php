<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Finance\Database\Factories\ExpenseFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'expense_date',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
