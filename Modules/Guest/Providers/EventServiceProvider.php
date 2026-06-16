<?php

namespace Modules\Guest\Providers;

use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Guest\Listeners\AktifkanFiturTamuSetelahSewaAktif;
use Modules\Guest\Listeners\NonaktifkanFiturTamuSetelahSewaSelesai;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JadwalSewaAktif::class => [
            AktifkanFiturTamuSetelahSewaAktif::class,
        ],
        JadwalSewaSelesai::class => [
            NonaktifkanFiturTamuSetelahSewaSelesai::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
