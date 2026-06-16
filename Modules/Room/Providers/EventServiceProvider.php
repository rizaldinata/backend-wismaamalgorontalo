<?php

namespace Modules\Room\Providers;

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Room\Listeners\TandaiKamarDipesanSetelahJadwalDibuat;
use Modules\Room\Listeners\TandaiKamarTerisiSetelahJadwalAktif;
use Modules\Room\Listeners\TandaiKamarTersediaSetelahJadwalSelesai;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JadwalDibuat::class => [
            TandaiKamarDipesanSetelahJadwalDibuat::class,
        ],
        JadwalSewaAktif::class => [
            TandaiKamarTerisiSetelahJadwalAktif::class,
        ],
        JadwalSewaSelesai::class => [
            TandaiKamarTersediaSetelahJadwalSelesai::class,
        ],
        JadwalBatal::class => [
            TandaiKamarTersediaSetelahJadwalSelesai::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
