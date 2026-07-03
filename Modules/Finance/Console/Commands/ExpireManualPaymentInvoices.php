<?php

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;

class ExpireManualPaymentInvoices extends Command
{
    protected $signature = 'finance:expire-manual-invoices';

    protected $description = 'Batalkan invoice perpanjangan sewa manual yang melewati batas waktu 15 menit dan belum ada pembayaran aktif';

    public function handle(): void
    {
        $activePaymentStatuses = [
            PaymentStatus::PENDING->value,
            PaymentStatus::VERIFIED->value,
        ];

        $expiredInvoices = DB::table('invoices')
            ->where('status', InvoiceStatus::UNPAID->value)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', now())
            ->whereNotExists(function ($query) use ($activePaymentStatuses) {
                $query->from('payments')
                    ->whereColumn('payments.invoice_id', 'invoices.id')
                    ->whereIn('payments.status', $activePaymentStatuses);
            })
            ->get();

        $cancelled = 0;

        foreach ($expiredInvoices as $invoice) {
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update([
                    'status' => InvoiceStatus::CANCELLED->value,
                    'updated_at' => now(),
                ]);

            Log::info('ExpireManualPaymentInvoices: invoice dibatalkan karena melewati batas waktu.', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'payment_expires_at' => $invoice->payment_expires_at,
            ]);

            $cancelled++;
        }

        $this->info("Selesai: {$cancelled} invoice dibatalkan karena melewati batas waktu pembayaran.");
    }
}
