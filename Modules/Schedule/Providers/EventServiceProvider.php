<?php

namespace Modules\Schedule\Providers;

use App\Events\Finance\PembayaranDibatalkan;
use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Schedule\Listeners\AktifkanJadwalSetelahPelunasan;
use Modules\Schedule\Listeners\AktifkanJadwalSetelahPembayaranDiterima;
use Modules\Schedule\Listeners\AktifkanJadwalSetelahPembayaranDiverifikasi;
use Modules\Schedule\Listeners\BatalkanJadwalSetelahPembayaranGagal;
use Modules\Schedule\Listeners\TandaiDPTerbayarSetelahPembayaranDP;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PembayaranDiverifikasi::class => [
            AktifkanJadwalSetelahPembayaranDiverifikasi::class,
            TandaiDPTerbayarSetelahPembayaranDP::class,
            AktifkanJadwalSetelahPelunasan::class,
        ],
        PembayaranDiterima::class => [
            AktifkanJadwalSetelahPembayaranDiterima::class,
            TandaiDPTerbayarSetelahPembayaranDP::class,
            AktifkanJadwalSetelahPelunasan::class,
        ],
        PembayaranDibatalkan::class => [
            BatalkanJadwalSetelahPembayaranGagal::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
