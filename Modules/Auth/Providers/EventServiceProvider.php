<?php

namespace Modules\Auth\Providers;

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Auth\Listeners\TingkatkanKeRoleResident;
use Modules\Auth\Listeners\TurunkanKeRoleMember;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JadwalSewaAktif::class => [
            TingkatkanKeRoleResident::class,
        ],
        JadwalSewaSelesai::class => [
            TurunkanKeRoleMember::class,
        ],
        JadwalBatal::class => [
            TurunkanKeRoleMember::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
