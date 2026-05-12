<?php

namespace App\Providers;

use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use App\Events\Jadwal\StatusKamarBerubah;
use App\Events\Maintenance\LaporanKerusakanMasuk;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JadwalDibuat::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalDibuat::class,
        ],
        JadwalSewaAktif::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalSewaAktif::class,
        ],
        JadwalSewaSelesai::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalSewaSelesai::class,
        ],
        JadwalBatal::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalBatal::class,
        ],
        StatusKamarBerubah::class => [],
        PembayaranDiterima::class => [
            \Modules\Notification\Listeners\KirimNotifikasiPembayaranDiterima::class,
        ],
        PembayaranDiverifikasi::class => [
            \Modules\Notification\Listeners\SendWhatsAppReceipt::class,
        ],
        LaporanKerusakanMasuk::class => [],
    ];
}
