<?php

namespace Modules\Finance\Listeners;

use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Support\Facades\DB;

class HapusTenantAktifSetelahSewaSelesai
{
    public function handle(JadwalSewaSelesai $event): void
    {
        DB::table('finance_active_tenants')
            ->where('schedule_id', $event->scheduleId)
            ->delete();
    }
}
