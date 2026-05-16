<?php

namespace Modules\Guest\Listeners;

use App\Events\Jadwal\JadwalSewaAktif;
use Illuminate\Support\Facades\Log;
use Modules\Guest\Models\GuestActiveContext;

class AktifkanFiturTamuSetelahSewaAktif
{
    public function handle(JadwalSewaAktif $event): void
    {
        if (! $event->userId) {
            Log::warning('AktifkanFiturTamu: event tanpa userId, dilewati.', [
                'schedule_id' => $event->scheduleId,
            ]);

            return;
        }

        GuestActiveContext::updateOrCreate(
            ['user_id' => $event->userId],
            [
                'schedule_id' => $event->scheduleId,
                'room_id' => $event->roomId,
                'room_price' => $event->roomPrice ?? 0,
                'tenant_name' => $event->tenantName,
                'tenant_email' => $event->tenantEmail,
                'tenant_phone' => $event->tenantPhone,
                'is_active' => true,
            ]
        );
    }
}
