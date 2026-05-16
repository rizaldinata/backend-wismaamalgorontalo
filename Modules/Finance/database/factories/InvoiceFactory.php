<?php

namespace Modules\Finance\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Enums\InvoiceStatus;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'schedule_id'    => null,
            'lease_id'       => null,
            'invoice_number' => 'INV-' . fake()->unique()->numerify('##########'),
            'amount'         => 500000,
            'status'         => InvoiceStatus::UNPAID,
            'due_date'       => now()->addDays(7),
        ];
    }
}
