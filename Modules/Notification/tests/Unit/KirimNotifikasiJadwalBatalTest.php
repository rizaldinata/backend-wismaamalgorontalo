<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Jadwal\JadwalBatal;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiJadwalBatal;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiJadwalBatalTest extends TestCase
{
    private NotificationService $notificationService;

    private SettingService $settingService;

    private KirimNotifikasiJadwalBatal $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->listener = new KirimNotifikasiJadwalBatal(
            $this->notificationService,
            $this->settingService,
        );
    }

    public function test_mengirim_notifikasi_ketika_ada_nomor_hp_dan_fitur_aktif(): void
    {
        $event = new JadwalBatal(
            scheduleId: 1,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Siti Rahma',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                NotificationType::JADWAL_BATAL,
                '08123456789',
                $this->stringContains('Siti Rahma')
            );

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_nomor_hp_tenant_kosong(): void
    {
        $event = new JadwalBatal(
            scheduleId: 2,
            roomId: 10,
            roomNumber: '101',
            tenantName: null,
            tenantPhone: null,
        );

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_fitur_whatsapp_dinonaktifkan(): void
    {
        $event = new JadwalBatal(
            scheduleId: 3,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Siti Rahma',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(false);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_nomor_kamar(): void
    {
        $event = new JadwalBatal(
            scheduleId: 4,
            roomId: 10,
            roomNumber: '101',
            tenantName: 'Siti Rahma',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->stringContains('101')
            );

        $this->listener->handle($event);
    }
}
