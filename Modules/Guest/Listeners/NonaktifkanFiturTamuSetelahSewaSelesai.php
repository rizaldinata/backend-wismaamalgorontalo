<?php

namespace Modules\Guest\Listeners;

use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Support\Facades\Log;
use Modules\Guest\Models\GuestActiveContext;

class NonaktifkanFiturTamuSetelahSewaSelesai
{
    public function handle(JadwalSewaSelesai $event): void
    {
        if (!$event->userId) {
            Log::warning('NonaktifkanFiturTamu: event tanpa userId, dilewati.', [
                'schedule_id' => $event->scheduleId,
            ]);
            return;
        }

        GuestActiveContext::where('user_id', $event->userId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
