<?php

namespace Modules\Auth\Listeners;

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Support\Facades\DB;
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

        // Jika user masih punya sewa aktif lain, jangan turunkan role.
        // Status sudah diubah ke finished/cancelled sebelum event difire,
        // sehingga query ini secara otomatis mengecualikan jadwal yang baru saja selesai.
        $masihAdaSewaAktif = DB::table('room_schedules')
            ->where('tenant_user_id', $event->userId)
            ->where('status', 'active')
            ->exists();

        if ($masihAdaSewaAktif) {
            return;
        }

        $user->syncRoles(['member']);
    }
}
