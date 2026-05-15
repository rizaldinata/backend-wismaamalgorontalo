<?php

namespace App\Providers;

use App\Events\Finance\PembayaranDibatalkan;
use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
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
    protected $listen = [
        JadwalDibuat::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalDibuat::class,
            \Modules\Finance\Listeners\BuatInvoiceSetelahJadwalDibuat::class,
        ],
        JadwalSewaAktif::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalSewaAktif::class,
            \Modules\Guest\Listeners\AktifkanFiturTamuSetelahSewaAktif::class,
        ],
        JadwalSewaSelesai::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalSewaSelesai::class,
            \Modules\Inventory\Listeners\BuatChecklistInventarisSetelahSewaSelesai::class,
            \Modules\Guest\Listeners\NonaktifkanFiturTamuSetelahSewaSelesai::class,
        ],
        JadwalBatal::class => [
            \Modules\Notification\Listeners\KirimNotifikasiJadwalBatal::class,
        ],
        StatusKamarBerubah::class => [],
        PembayaranDiterima::class => [
            \Modules\Notification\Listeners\KirimNotifikasiPembayaranDiterima::class,
            \Modules\Rental\Listeners\AktifkanLeaseSetelahPembayaranDiterima::class,
        ],
        PembayaranDiverifikasi::class => [
            \Modules\Notification\Listeners\SendWhatsAppReceipt::class,
            \Modules\Rental\Listeners\AktifkanLeaseSetelahPembayaranDiverifikasi::class,
        ],
        PembayaranDibatalkan::class => [
            \Modules\Rental\Listeners\BatalkanLeaseSetelahPembayaranDibatalkan::class,
        ],
        LaporanKerusakanMasuk::class => [],

        InventariBaru::class => [
            \Modules\Finance\Listeners\CatatPengeluaranInventariBaru::class,
        ],
        InventarisDiperbarui::class => [
            \Modules\Finance\Listeners\SinkronisasiPengeluaranInventaris::class,
        ],
        InventarisDihapus::class => [
            \Modules\Finance\Listeners\HapusPengeluaranInventaris::class,
        ],
    ];
}
