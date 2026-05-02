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
            $amount = $lease->room->price ?? 500000;
            if ($lease->rental_type === 'daily') {
                $amount = $lease->room->price_daily ?? 150000;
            }

            if ($lease->status->value === 'active') {
                // Buat 1 invoice bulan ini (ada yang lunas, ada yang belum)
                $isPaidThisMonth = $index % 3 !== 0; // Sebagian besar lunas
                
                $invoice = Invoice::create([
                    'lease_id' => $lease->id,
                    'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad($lease->id, 4, '0', STR_PAD_LEFT),
                    'amount' => $amount,
                    'status' => $isPaidThisMonth ? InvoiceStatus::PAID->value : InvoiceStatus::UNPAID->value,
                    'due_date' => Carbon::now()->subDays(rand(1, 10)), 
                    'created_at' => Carbon::now()->subDays(rand(1, 5)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 3)),
                ]);

                // Berikan payment
                if ($isPaidThisMonth) {
                    \Modules\Finance\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'payment_method' => rand(0, 1) ? 'manual' : 'qris',
                        'status' => 'verified',
                        'created_at' => $invoice->updated_at,
                        'updated_at' => $invoice->updated_at,
                    ]);
                } else if (rand(0, 1)) {
                    // Ada yang nyangkut di pending
                    \Modules\Finance\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'payment_method' => 'manual',
                        'payment_proof_path' => 'dummy_proof.jpg',
                        'status' => 'pending',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }

                // Buat invoice historis (lunas semua) untuk 5 bulan ke belakang agar chart terisi
                for ($i = 1; $i <= 5; $i++) {
                    $pastDate = Carbon::now()->subMonths($i)->startOfMonth()->addDays(rand(1, 5));
                    
                    $pastInvoice = Invoice::create([
                        'lease_id' => $lease->id,
                        'invoice_number' => 'INV-' . $pastDate->format('Ymd') . '-' . str_pad($lease->id, 4, '0', STR_PAD_LEFT),
                        'amount' => $amount,
                        'status' => InvoiceStatus::PAID->value,
                        'due_date' => (clone $pastDate)->addDays(5),
                        'created_at' => $pastDate,
                        'updated_at' => (clone $pastDate)->addDays(rand(1, 4)),
                    ]);

                    \Modules\Finance\Models\Payment::create([
                        'invoice_id' => $pastInvoice->id,
                        'payment_method' => 'manual',
                        'status' => 'verified',
                        'created_at' => $pastInvoice->updated_at,
                        'updated_at' => $pastInvoice->updated_at,
                    ]);
                }

            } else if ($lease->status->value === 'pending') {
                $invoice = Invoice::create([
                    'lease_id' => $lease->id,
                    'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad($lease->id, 4, '0', STR_PAD_LEFT),
                    'amount' => $amount,
                    'status' => InvoiceStatus::UNPAID->value,
                    'due_date' => Carbon::now()->addDays(rand(1, 5)),
                ]);

                // Beri beberapa status pending payment untuk diverifikasi admin
                if ($index % 2 == 0) {
                    \Modules\Finance\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'payment_method' => 'manual',
                        'payment_proof_path' => 'dummy_proof_pending.jpg',
                        'status' => 'pending',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }
    }
}
