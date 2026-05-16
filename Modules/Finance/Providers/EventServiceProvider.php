<?php

namespace Modules\Finance\Providers;

use App\Events\Inventory\InventariBaru;
use App\Events\Inventory\InventarisDihapus;
use App\Events\Inventory\InventarisDiperbarui;
use App\Events\Jadwal\JadwalDibuat;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Finance\Listeners\BuatInvoiceSetelahJadwalDibuat;
use Modules\Finance\Listeners\CatatPengeluaranInventariBaru;
use Modules\Finance\Listeners\HapusPengeluaranInventaris;
use Modules\Finance\Listeners\SinkronisasiPengeluaranInventaris;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JadwalDibuat::class => [
            BuatInvoiceSetelahJadwalDibuat::class,
        ],
        InventariBaru::class => [
            CatatPengeluaranInventariBaru::class,
        ],
        InventarisDiperbarui::class => [
            SinkronisasiPengeluaranInventaris::class,
        ],
        InventarisDihapus::class => [
            HapusPengeluaranInventaris::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
