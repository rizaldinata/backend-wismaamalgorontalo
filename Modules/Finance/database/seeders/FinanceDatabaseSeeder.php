<?php

namespace Modules\Finance\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Rental\Models\Lease;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Carbon\Carbon;

class FinanceDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $lease = Lease::first();

        if ($lease) {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($lease->id, 4, '0', STR_PAD_LEFT);

            Invoice::updateOrCreate(
                ['lease_id' => $lease->id],
                [
                    'invoice_number' => $invoiceNumber,
                    'amount' => 500000,
                    'status' => InvoiceStatus::UNPAID->value,
                    'due_date' => Carbon::parse($lease->start_date),
                ]
            );
        }
    }
}
