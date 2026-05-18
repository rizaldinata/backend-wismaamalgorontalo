<?php

namespace Modules\Finance\Listeners;

use App\Events\Jadwal\JadwalBatal;
use Illuminate\Support\Facades\DB;

class HapusTenantAktifSetelahJadwalBatal
{
    public function handle(JadwalBatal $event): void
    {
        DB::table('finance_active_tenants')
            ->where('schedule_id', $event->scheduleId)
            ->delete();
    }
}
