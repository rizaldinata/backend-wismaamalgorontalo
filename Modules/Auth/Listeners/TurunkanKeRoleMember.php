<?php

namespace Modules\Auth\Listeners;

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;

class TurunkanKeRoleMember
{
    public function handle(JadwalSewaSelesai|JadwalBatal $event): void
    {
        if (! $event->userId) {
            return;
        }

        // JadwalBatal bisa dari jadwal tipe selain sewa — skip jika bukan sewa
        if ($event instanceof JadwalBatal && $event->tipeJadwal !== 'sewa') {
            return;
        }

        $user = User::find($event->userId);

        if (! $user) {
            Log::warning('TurunkanKeRoleMember: user tidak ditemukan', ['user_id' => $event->userId]);

            return;
        }

        if (! $user->hasRole('resident')) {
            return;
        }

        if ($event->masihAdaSewaAktif) {
            return;
        }

        $user->syncRoles(['member']);
    }
}
