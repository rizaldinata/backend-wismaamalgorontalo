<?php

namespace Tests\Feature\Events;

use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Support\Facades\Event;
use Modules\Notification\Listeners\KirimNotifikasiJadwalBatal;
use Modules\Notification\Listeners\KirimNotifikasiJadwalDibuat;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaAktif;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaSelesai;
use Modules\Notification\Listeners\KirimNotifikasiPembayaranDiterima;
use Modules\Notification\Listeners\SendWhatsAppReceipt;
use Tests\TestCase;

class EventServiceProviderTest extends TestCase
{
    public function test_jadwal_dibuat_terhubung_ke_listener_notifikasi(): void
    {
        Event::fake();
        Event::assertListening(JadwalDibuat::class, KirimNotifikasiJadwalDibuat::class);
    }

    public function test_jadwal_sewa_aktif_terhubung_ke_listener_notifikasi(): void
    {
        Event::fake();
        Event::assertListening(JadwalSewaAktif::class, KirimNotifikasiJadwalSewaAktif::class);
    }

    public function test_jadwal_sewa_selesai_terhubung_ke_listener_notifikasi(): void
    {
        Event::fake();
        Event::assertListening(JadwalSewaSelesai::class, KirimNotifikasiJadwalSewaSelesai::class);
    }

    public function test_jadwal_batal_terhubung_ke_listener_notifikasi(): void
    {
        Event::fake();
        Event::assertListening(JadwalBatal::class, KirimNotifikasiJadwalBatal::class);
    }

    public function test_pembayaran_diterima_terhubung_ke_listener_notifikasi(): void
    {
        Event::fake();
        Event::assertListening(PembayaranDiterima::class, KirimNotifikasiPembayaranDiterima::class);
    }

    public function test_pembayaran_diverifikasi_terhubung_ke_listener_notifikasi(): void
    {
        Event::fake();
        Event::assertListening(PembayaranDiverifikasi::class, SendWhatsAppReceipt::class);
    }
}
