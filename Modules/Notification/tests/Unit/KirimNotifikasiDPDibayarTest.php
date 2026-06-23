<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Jadwal\DPDibayar;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiDPDibayar;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiDPDibayarTest extends TestCase
{
    private NotificationService $notificationService;

    private SettingService $settingService;

    private KirimNotifikasiDPDibayar $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService      = $this->createMock(SettingService::class);
        $this->listener            = new KirimNotifikasiDPDibayar(
            $this->notificationService,
            $this->settingService,
        );
    }

    private function buatEvent(string $tenantPhone = '08123456789'): DPDibayar
    {
        return new DPDibayar(
            scheduleId:         1,
            roomNumber:         '101',
            tenantName:         'Budi Santoso',
            tenantPhone:        $tenantPhone,
            dpAmount:           500000.0,
            pelunasanAmount:    1000000.0,
            startDate:          '01 Jan 2025',
            endDate:            '01 Jun 2025',
            roomNumberSnapshot: '101',
            periodStart:        '2025-01-01',
            periodEnd:          '2025-06-01',
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
                NotificationType::JADWAL_DIBUAT,
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
        $event = $this->buatEvent(tenantPhone: '');

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_jumlah_dp_dan_sisa_pelunasan_terformat(): void
    {
        $event = $this->buatEvent();

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->logicalAnd(
                    $this->stringContains('500.000'),
                    $this->stringContains('1.000.000'),
                ),
            );

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_tanggal_mulai_sewa(): void
    {
        $event = $this->buatEvent();

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->stringContains('01 Jan 2025'),
            );

        $this->listener->handle($event);
    }
}
