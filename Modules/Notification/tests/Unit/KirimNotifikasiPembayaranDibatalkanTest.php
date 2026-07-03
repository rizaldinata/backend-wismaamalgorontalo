<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Finance\PembayaranDibatalkan;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiPembayaranDibatalkan;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiPembayaranDibatalkanTest extends TestCase
{
    private NotificationService $notificationService;

    private SettingService $settingService;

    private KirimNotifikasiPembayaranDibatalkan $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService = $this->createMock(SettingService::class);
        $this->listener = new KirimNotifikasiPembayaranDibatalkan(
            $this->notificationService,
            $this->settingService,
        );
    }

    private function buatEvent(?string $tenantPhone = '08123456789', ?float $amount = 750000.0): PembayaranDibatalkan
    {
        return new PembayaranDibatalkan(
            paymentId: 20,
            invoiceId: 30,
            scheduleId: 1,
            tenantName: 'Budi Santoso',
            tenantPhone: $tenantPhone,
            amount: $amount,
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
                NotificationType::PEMBAYARAN_DIBATALKAN,
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
        $event = $this->buatEvent(tenantPhone: null);

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_jumlah_pembayaran_terformat(): void
    {
        $event = $this->buatEvent(amount: 1500000.0);

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->stringContains('1.500.000'),
            );

        $this->listener->handle($event);
    }

    public function test_pesan_tanpa_jumlah_jika_amount_null(): void
    {
        $event = $this->buatEvent(amount: null);

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->logicalNot($this->stringContains('Rp')),
            );

        $this->listener->handle($event);
    }
}
