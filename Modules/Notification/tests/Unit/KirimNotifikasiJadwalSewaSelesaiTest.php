<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Jadwal\JadwalSewaSelesai;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiJadwalSewaSelesai;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiJadwalSewaSelesaiTest extends TestCase
{
    private NotificationService $notificationService;

    private SettingService $settingService;

    private KirimNotifikasiJadwalSewaSelesai $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->listener = new KirimNotifikasiJadwalSewaSelesai(
            $this->notificationService,
            $this->settingService,
        );
    }

    public function test_mengirim_notifikasi_ketika_fitur_aktif(): void
    {
        $event = new JadwalSewaSelesai(
            scheduleId: 1,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            endDate: '01 Jul 2025',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                NotificationType::JADWAL_SEWA_SELESAI,
                '08111111111',
                $this->stringContains('Ahmad Fauzi')
            );

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_fitur_whatsapp_dinonaktifkan(): void
    {
        $event = new JadwalSewaSelesai(
            scheduleId: 2,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            endDate: '2025-07-01',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(false);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_nomor_kamar_dan_tanggal_selesai(): void
    {
        $event = new JadwalSewaSelesai(
            scheduleId: 3,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Ahmad Fauzi',
            tenantPhone: '08111111111',
            endDate: '01 Jul 2025',
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
                    $this->stringContains('01 Jul 2025')
                )
            );

        $this->listener->handle($event);
    }
}
