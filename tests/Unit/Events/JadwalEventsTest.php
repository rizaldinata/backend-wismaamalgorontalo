<?php

namespace Tests\Unit\Events;

use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalDibuat;
use App\Events\Jadwal\JadwalSewaAktif;
use App\Events\Jadwal\JadwalSewaSelesai;
use App\Events\Jadwal\StatusKamarBerubah;
use App\Events\Maintenance\LaporanKerusakanMasuk;
use PHPUnit\Framework\TestCase;

class JadwalEventsTest extends TestCase
{
    public function test_jadwal_dibuat_menyimpan_semua_properti(): void
    {
        $event = new JadwalDibuat(
            scheduleId: 1,
            roomId: 10,
            roomNumber: '101',
            tipeJadwal: 'sewa',
            startDate: '2025-01-01',
            endDate: '2025-06-01',
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
            agreedPrice: 1500000.0,
        );

        $this->assertEquals(1, $event->scheduleId);
        $this->assertEquals(10, $event->roomId);
        $this->assertEquals('101', $event->roomNumber);
        $this->assertEquals('sewa', $event->tipeJadwal);
        $this->assertEquals('2025-01-01', $event->startDate);
        $this->assertEquals('2025-06-01', $event->endDate);
        $this->assertEquals('Budi Santoso', $event->tenantName);
        $this->assertEquals('08123456789', $event->tenantPhone);
        $this->assertEquals(1500000.0, $event->agreedPrice);
    }

    public function test_jadwal_dibuat_properti_opsional_boleh_null(): void
    {
        $event = new JadwalDibuat(
            scheduleId: 2,
            roomId: 11,
            roomNumber: '102',
            tipeJadwal: 'maintenance',
            startDate: '2025-01-01',
            endDate: '2025-01-07',
        );

        $this->assertNull($event->tenantName);
        $this->assertNull($event->tenantPhone);
        $this->assertNull($event->agreedPrice);
    }

    public function test_jadwal_batal_menyimpan_semua_properti(): void
    {
        $event = new JadwalBatal(
            scheduleId: 5,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Siti Rahma',
            tenantPhone: '08987654321',
        );

        $this->assertEquals(5, $event->scheduleId);
        $this->assertEquals(10, $event->roomId);
        $this->assertEquals('101', $event->roomNumber);
        $this->assertEquals('Siti Rahma', $event->tenantName);
        $this->assertEquals('08987654321', $event->tenantPhone);
    }

    public function test_jadwal_batal_tanpa_tenant(): void
    {
        $event = new JadwalBatal(scheduleId: 6, roomId: 12, roomNumber: '103');

        $this->assertNull($event->tenantName);
        $this->assertNull($event->tenantPhone);
    }

    public function test_jadwal_sewa_aktif_menyimpan_semua_properti(): void
    {
        $event = new JadwalSewaAktif(
            scheduleId: 3,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            startDate: '2025-02-01',
        );

        $this->assertEquals(3, $event->scheduleId);
        $this->assertEquals('Ahmad Fauzi', $event->tenantName);
        $this->assertEquals('2025-02-01', $event->startDate);
    }

    public function test_jadwal_sewa_selesai_menyimpan_semua_properti(): void
    {
        $event = new JadwalSewaSelesai(
            scheduleId: 4,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            endDate: '2025-07-01',
        );

        $this->assertEquals(4, $event->scheduleId);
        $this->assertEquals('Ahmad Fauzi', $event->tenantName);
        $this->assertEquals('2025-07-01', $event->endDate);
    }

    public function test_pembayaran_diterima_menyimpan_semua_properti(): void
    {
        $event = new PembayaranDiterima(
            paymentId: 20,
            invoiceId: 30,
            scheduleId: 1,
            amount: 750000.0,
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
        );

        $this->assertEquals(20, $event->paymentId);
        $this->assertEquals(30, $event->invoiceId);
        $this->assertEquals(1, $event->scheduleId);
        $this->assertEquals(750000.0, $event->amount);
        $this->assertEquals('Budi Santoso', $event->tenantName);
        $this->assertEquals('08123456789', $event->tenantPhone);
    }

    public function test_pembayaran_diverifikasi_menyimpan_semua_properti(): void
    {
        $event = new PembayaranDiverifikasi(
            paymentId: 21,
            invoiceId: 31,
            scheduleId: 1,
            amount: 1500000.0,
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
            invoiceNumber: 'INV-001',
            roomTitle: 'Kamar Standar',
            roomNumber: '101',
            startDate: '2025-01-01',
            endDate: '2025-06-01',
        );

        $this->assertEquals(21, $event->paymentId);
        $this->assertEquals('INV-001', $event->invoiceNumber);
        $this->assertEquals('Kamar Standar', $event->roomTitle);
        $this->assertEquals('101', $event->roomNumber);
        $this->assertEquals('2025-01-01', $event->startDate);
        $this->assertEquals('2025-06-01', $event->endDate);
    }

    public function test_laporan_kerusakan_masuk_menyimpan_semua_properti(): void
    {
        $event = new LaporanKerusakanMasuk(
            reportId: 7,
            reporterName: 'Rizky',
            reporterPhone: '08222222222',
            description: 'AC bocor',
            roomId: 10,
            roomNumber: '101',
        );

        $this->assertEquals(7, $event->reportId);
        $this->assertEquals('Rizky', $event->reporterName);
        $this->assertEquals('AC bocor', $event->description);
    }

    public function test_status_kamar_berubah_menyimpan_semua_properti(): void
    {
        $event = new StatusKamarBerubah(
            roomId: 10,
            roomNumber: '101',
            statusLama: 'available',
            statusBaru: 'occupied',
        );

        $this->assertEquals(10, $event->roomId);
        $this->assertEquals('available', $event->statusLama);
        $this->assertEquals('occupied', $event->statusBaru);
    }
}
