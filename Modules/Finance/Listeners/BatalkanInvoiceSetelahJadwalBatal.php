<?php

namespace Modules\Finance\Listeners;

use App\Events\Jadwal\JadwalBatal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatalkanInvoiceSetelahJadwalBatal
{
    public function handle(JadwalBatal $event): void
    {
        $cancelled = DB::table('invoices')
            ->where('schedule_id', $event->scheduleId)
            ->where('status', 'unpaid')
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        if ($cancelled > 0) {
            Log::info('BatalkanInvoiceSetelahJadwalBatal: invoice unpaid dibatalkan.', [
                'schedule_id' => $event->scheduleId,
                'count' => $cancelled,
            ]);
        }
    }
}
