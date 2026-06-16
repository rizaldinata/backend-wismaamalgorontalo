<?php

namespace App\Providers;

use App\Events\Finance\PembayaranDibatalkan;
use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use Modules\Finance\Events\PaymentSettled;
use App\Events\Inventory\InventariBaru;
use App\Events\Inventory\InventarisDihapus;
use App\Events\Inventory\InventarisDiperbarui;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use App\Events\Jadwal\StatusKamarBerubah;
use App\Events\Maintenance\LaporanKerusakanMasuk;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    // Listeners are registered in each module's own EventServiceProvider.
    // This global provider only declares the event catalog so Laravel knows
    // which events exist; business modules self-register their listeners.
    protected $listen = [
        JadwalDibuat::class => [],
        JadwalSewaAktif::class => [],
        JadwalSewaSelesai::class => [],
        JadwalBatal::class => [],
        StatusKamarBerubah::class => [],
        PembayaranDiterima::class => [],
        PembayaranDiverifikasi::class => [],
        PembayaranDibatalkan::class => [],
        PaymentSettled::class => [],
        LaporanKerusakanMasuk::class => [],
        InventariBaru::class => [],
        InventarisDiperbarui::class => [],
        InventarisDihapus::class => [],
    ];
}
