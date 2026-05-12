<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Jadwal\JadwalSewaAktif;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaAktif;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiJadwalSewaAktifTest extends TestCase
{
    private NotificationService $notificationService;
    private SettingService $settingService;
    private KirimNotifikasiJadwalSewaAktif $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService      = $this->createMock(SettingService::class);
        $this->listener            = new KirimNotifikasiJadwalSewaAktif(
            $this->notificationService,
            $this->settingService,
        );
    }

    public function test_mengirim_notifikasi_ketika_fitur_aktif(): void
    {
        $event = new JadwalSewaAktif(
            scheduleId: 1,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            startDate: '01 Feb 2025',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                NotificationType::JADWAL_SEWA_AKTIF,
                '08111111111',
                $this->stringContains('Ahmad Fauzi')
            );

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_fitur_whatsapp_dinonaktifkan(): void
    {
        $event = new JadwalSewaAktif(
            scheduleId: 2,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            startDate: '2025-02-01',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(false);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_nomor_kamar_dan_tanggal_mulai(): void
    {
        $event = new JadwalSewaAktif(
            scheduleId: 3,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            startDate: '01 Feb 2025',
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
                    $this->stringContains('01 Feb 2025')
                )
            );

        $this->listener->handle($event);
    }
}
