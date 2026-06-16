<?php

namespace Modules\Notification\Providers;

use App\Events\Finance\PembayaranDibatalkan;
use App\Events\Finance\PembayaranDiverifikasi;
use App\Events\Maintenance\LaporanKerusakanMasuk;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notification\Listeners\KirimNotifikasiJadwalBatal;
use Modules\Notification\Listeners\KirimNotifikasiJadwalDibuat;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaAktif;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaSelesai;
use Modules\Notification\Listeners\KirimNotifikasiLaporanKerusakanMasuk;
use Modules\Notification\Listeners\KirimNotifikasiPembayaranDibatalkan;
use Modules\Notification\Listeners\SendWhatsAppReceipt;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JadwalDibuat::class => [
            KirimNotifikasiJadwalDibuat::class,
        ],
        JadwalSewaAktif::class => [
            KirimNotifikasiJadwalSewaAktif::class,
        ],
        JadwalSewaSelesai::class => [
            KirimNotifikasiJadwalSewaSelesai::class,
        ],
        JadwalBatal::class => [
            KirimNotifikasiJadwalBatal::class,
        ],
        PembayaranDibatalkan::class => [
            KirimNotifikasiPembayaranDibatalkan::class,
        ],
        LaporanKerusakanMasuk::class => [
            KirimNotifikasiLaporanKerusakanMasuk::class,
        ],
        PembayaranDiverifikasi::class => [
            SendWhatsAppReceipt::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
