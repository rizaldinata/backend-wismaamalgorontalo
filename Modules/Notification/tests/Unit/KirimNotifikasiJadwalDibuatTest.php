<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Jadwal\JadwalDibuat;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiJadwalDibuat;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiJadwalDibuatTest extends TestCase
{
    private NotificationService $notificationService;

    private SettingService $settingService;

    private KirimNotifikasiJadwalDibuat $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->listener = new KirimNotifikasiJadwalDibuat(
            $this->notificationService,
            $this->settingService,
        );
    }

    public function test_mengirim_notifikasi_ketika_jadwal_sewa_dengan_nomor_hp_dan_fitur_aktif(): void
    {
        $event = new JadwalDibuat(
            scheduleId: 1,
            roomId: 10,
            roomNumber: '101',
            tipeJadwal: 'sewa',
            startDate: '01 Jan 2025',
            endDate: '01 Jun 2025',
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                NotificationType::JADWAL_DIBUAT,
                '08123456789',
                $this->stringContains('Budi Santoso')
            );

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_tipe_jadwal_bukan_sewa(): void
    {
        $event = new JadwalDibuat(
            scheduleId: 2,
            roomId: 10,
            roomNumber: '101',
            tipeJadwal: 'maintenance',
            startDate: '2025-01-01',
            endDate: '2025-01-07',
        );

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_nomor_hp_tenant_kosong(): void
    {
        $event = new JadwalDibuat(
            scheduleId: 3,
            roomId: 10,
            roomNumber: '101',
            tipeJadwal: 'sewa',
            startDate: '2025-01-01',
            endDate: '2025-06-01',
            tenantName: 'Budi Santoso',
            tenantPhone: null,
        );

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_fitur_whatsapp_dinonaktifkan(): void
    {
        $event = new JadwalDibuat(
            scheduleId: 4,
            roomId: 10,
            roomNumber: '101',
            tipeJadwal: 'sewa',
            startDate: '2025-01-01',
            endDate: '2025-06-01',
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(false);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_nomor_kamar_dan_periode_sewa(): void
    {
        $event = new JadwalDibuat(
            scheduleId: 5,
            roomId: 10,
            roomNumber: '101',
            tipeJadwal: 'sewa',
            startDate: '01 Jan 2025',
            endDate: '01 Jun 2025',
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->logicalAnd(
                    $this->stringContains('101'),
                    $this->stringContains('01 Jan 2025'),
                    $this->stringContains('01 Jun 2025')
                )
            );

        $this->listener->handle($event);
    }
}
