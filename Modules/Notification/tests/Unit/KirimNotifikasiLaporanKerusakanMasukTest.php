<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Maintenance\LaporanKerusakanMasuk;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiLaporanKerusakanMasuk;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiLaporanKerusakanMasukTest extends TestCase
{
    private NotificationService $notificationService;

    private SettingService $settingService;

    private KirimNotifikasiLaporanKerusakanMasuk $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService      = $this->createMock(SettingService::class);
        $this->listener            = new KirimNotifikasiLaporanKerusakanMasuk(
            $this->notificationService,
            $this->settingService,
        );
    }

    private function buatEvent(string $reporterPhone = '08123456789', ?string $roomNumber = '101'): LaporanKerusakanMasuk
    {
        return new LaporanKerusakanMasuk(
            reportId:      1,
            reporterName:  'Budi Santoso',
            reporterPhone: $reporterPhone,
            description:   'AC tidak dingin',
            roomId:        $roomNumber ? 1 : null,
            roomNumber:    $roomNumber,
        );
    }

    public function test_mengirim_notifikasi_ketika_fitur_aktif(): void
    {
        $event = $this->buatEvent();

        $this->settingService
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['notif_receipt', true],
            ]);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                NotificationType::LAPORAN_KERUSAKAN,
                '08123456789',
                $this->stringContains('Budi Santoso'),
            );

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_fitur_nonaktif(): void
    {
        $event = $this->buatEvent();

        $this->settingService
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['notif_receipt', false],
            ]);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_nomor_hp_kosong(): void
    {
        $event = $this->buatEvent(reporterPhone: '');

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_deskripsi_kerusakan(): void
    {
        $event = $this->buatEvent();

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->stringContains('AC tidak dingin'),
            );

        $this->listener->handle($event);
    }

    public function test_pesan_menyertakan_nomor_kamar_jika_ada(): void
    {
        $event = $this->buatEvent(roomNumber: '101');

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->stringContains('Kamar 101'),
            );

        $this->listener->handle($event);
    }

    public function test_pesan_tanpa_info_kamar_jika_roomNumber_null(): void
    {
        $event = $this->buatEvent(roomNumber: null);

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->logicalNot($this->stringContains('Kamar')),
            );

        $this->listener->handle($event);
    }
}
