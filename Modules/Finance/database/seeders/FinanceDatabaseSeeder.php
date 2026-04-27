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
        $leases = Lease::with('room')->get();

        foreach ($leases as $index => $lease) {
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($lease->id, 4, '0', STR_PAD_LEFT);
            $amount = $lease->room->price ?? 500000;

            if ($lease->status->value === 'active') {
                // Untuk status aktif, kita buat ada yang Lunas (mayoritas) dan Belum Lunas (telat bayar)
                // Misalnya dari 7 yang aktif, 5 lunas, 2 belum lunas
                $isPaid = $index < 5;
                
                Invoice::create([
                    'lease_id' => $lease->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $amount,
                    'status' => $isPaid ? InvoiceStatus::PAID->value : InvoiceStatus::UNPAID->value,
                    'due_date' => Carbon::now()->subDays(rand(1, 10)), 
                ]);
            } else if ($lease->status->value === 'pending') {
                // Untuk status pending, pasti belum lunas
                Invoice::create([
                    'lease_id' => $lease->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $amount,
                    'status' => InvoiceStatus::UNPAID->value,
                    'due_date' => Carbon::now()->addDays(rand(1, 5)),
                ]);
            }
        }
    }
}
