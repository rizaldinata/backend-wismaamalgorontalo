<?php

namespace Modules\Auth\Listeners;

use App\Events\Jadwal\JadwalSewaAktif;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;

class TingkatkanKeRoleResident
{
    public function handle(JadwalSewaAktif $event): void
    {
        if (! $event->userId) {
            return;
        }

        $user = User::find($event->userId);

        if (! $user) {
            Log::warning('TingkatkanKeRoleResident: user tidak ditemukan', ['user_id' => $event->userId]);
            return;
        }

        if ($user->hasRole('resident')) {
            return;
        }

        $user->syncRoles(['resident']);
    }
}
