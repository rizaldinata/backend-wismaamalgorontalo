<?php

namespace Modules\Notification\Providers;

use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notification\Listeners\KirimNotifikasiJadwalBatal;
use Modules\Notification\Listeners\KirimNotifikasiJadwalDibuat;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaAktif;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaSelesai;
use Modules\Notification\Listeners\KirimNotifikasiPembayaranDiterima;
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
        PembayaranDiterima::class => [
            KirimNotifikasiPembayaranDiterima::class,
        ],
        PembayaranDiverifikasi::class => [
            SendWhatsAppReceipt::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
