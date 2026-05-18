<?php

namespace Modules\Finance\Listeners;

use App\Events\Jadwal\JadwalSewaAktif;
use Illuminate\Support\Facades\DB;

class CatatTenantAktifSetelahJadwalSewaAktif
{
    public function handle(JadwalSewaAktif $event): void
    {
        if (! $event->userId) {
            return;
        }

        DB::table('finance_active_tenants')->updateOrInsert(
            ['schedule_id' => $event->scheduleId],
            [
                'user_id' => $event->userId,
                'room_number' => $event->roomNumber,
                'tenant_name' => $event->tenantName,
                'tenant_phone' => $event->tenantPhone,
                'start_date' => $event->startDate,
                'end_date' => $event->endDate,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
