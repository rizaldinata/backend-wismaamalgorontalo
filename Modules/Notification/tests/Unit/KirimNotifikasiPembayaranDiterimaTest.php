<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Finance\PembayaranDiterima;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Listeners\KirimNotifikasiPembayaranDiterima;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\TestCase;

class KirimNotifikasiPembayaranDiterimaTest extends TestCase
{
    private NotificationService $notificationService;
    private SettingService $settingService;
    private KirimNotifikasiPembayaranDiterima $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService      = $this->createMock(SettingService::class);
        $this->listener            = new KirimNotifikasiPembayaranDiterima(
            $this->notificationService,
            $this->settingService,
        );
    }

    public function test_mengirim_notifikasi_ketika_fitur_aktif(): void
    {
        $event = new PembayaranDiterima(
            paymentId: 20,
            invoiceId: 30,
            scheduleId: 1,
            amount: 750000.0,
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(true);

        $this->notificationService
            ->expects($this->once())
            ->method('sendNotification')
            ->with(
                NotificationType::PEMBAYARAN_DITERIMA,
                '08123456789',
                $this->stringContains('Budi Santoso')
            );

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_fitur_whatsapp_dinonaktifkan(): void
    {
        $event = new PembayaranDiterima(
            paymentId: 21,
            invoiceId: 31,
            scheduleId: 1,
            amount: 750000.0,
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
        );

        $this->settingService->method('isFeatureEnabled')->willReturn(false);

        $this->notificationService->expects($this->never())->method('sendNotification');

        $this->listener->handle($event);
    }

    public function test_pesan_memuat_jumlah_pembayaran_terformat(): void
    {
        $event = new PembayaranDiterima(
            paymentId: 22,
            invoiceId: 32,
            scheduleId: 1,
            amount: 1500000.0,
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
                $this->stringContains('1.500.000')
            );

        $this->listener->handle($event);
    }
}
