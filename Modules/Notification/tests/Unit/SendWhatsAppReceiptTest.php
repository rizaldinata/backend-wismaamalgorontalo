<?php

namespace Modules\Notification\Tests\Unit;

use App\Events\Finance\PembayaranDiverifikasi;
use Illuminate\Support\Facades\URL;
use Modules\Notification\Listeners\SendWhatsAppReceipt;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

class SendWhatsAppReceiptTest extends TestCase
{
    private NotificationService $notificationService;
    private SettingService $settingService;
    private SendWhatsAppReceipt $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->settingService      = $this->createMock(SettingService::class);
        $this->listener            = new SendWhatsAppReceipt(
            $this->notificationService,
            $this->settingService,
        );
    }

    private function buatEvent(): PembayaranDiverifikasi
    {
        return new PembayaranDiverifikasi(
            paymentId: 21,
            invoiceId: 31,
            scheduleId: 1,
            amount: 1500000.0,
            tenantName: 'Budi Santoso',
            tenantPhone: '08123456789',
            invoiceNumber: 'INV-001',
            roomTitle: 'Kamar Standar',
            roomNumber: '101',
            startDate: '01 Jan 2025',
            endDate: '01 Jun 2025',
        );
    }

    public function test_mengirim_receipt_ketika_fitur_aktif_tanpa_pdf_link(): void
    {
        $event = $this->buatEvent();

        $this->settingService
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['feature_whatsapp_receipt', true],
                ['feature_whatsapp_pdf_link', false],
            ]);

        $this->notificationService
            ->expects($this->once())
            ->method('sendReceiptFromPrimitives')
            ->with(
                tenantName: 'Budi Santoso',
                tenantPhone: '08123456789',
                invoiceNumber: 'INV-001',
                roomTitle: 'Kamar Standar',
                roomNumber: '101',
                periode: '01 Jan 2025 - 01 Jun 2025',
                amount: 1500000.0,
                pdfLink: null,
            );

        $this->listener->handle($event);
    }

    public function test_tidak_mengirim_jika_fitur_whatsapp_dinonaktifkan(): void
    {
        $event = $this->buatEvent();

        $this->settingService
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['feature_whatsapp_receipt', false],
                ['feature_whatsapp_pdf_link', false],
            ]);

        $this->notificationService->expects($this->never())->method('sendReceiptFromPrimitives');

        $this->listener->handle($event);
    }

    public function test_mengirim_dengan_pdf_link_ketika_fitur_pdf_aktif(): void
    {
        $event = $this->buatEvent();

        $this->settingService
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['feature_whatsapp_receipt', true],
                ['feature_whatsapp_pdf_link', true],
            ]);

        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->with('finance.invoice.print', \Mockery::any(), ['id' => 31])
            ->andReturn('https://example.com/invoice/31?signature=abc');

        $this->notificationService
            ->expects($this->once())
            ->method('sendReceiptFromPrimitives')
            ->with(
                tenantName: 'Budi Santoso',
                tenantPhone: '08123456789',
                invoiceNumber: 'INV-001',
                roomTitle: 'Kamar Standar',
                roomNumber: '101',
                periode: '01 Jan 2025 - 01 Jun 2025',
                amount: 1500000.0,
                pdfLink: 'https://example.com/invoice/31?signature=abc',
            );

        $this->listener->handle($event);
    }

    public function test_periode_digabung_dari_start_dan_end_date(): void
    {
        $event = $this->buatEvent();

        $this->settingService
            ->method('isFeatureEnabled')
            ->willReturnMap([
                ['feature_whatsapp_receipt', true],
                ['feature_whatsapp_pdf_link', false],
            ]);

        $this->notificationService
            ->expects($this->once())
            ->method('sendReceiptFromPrimitives')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                '01 Jan 2025 - 01 Jun 2025',
                $this->anything(),
                $this->anything(),
            );

        $this->listener->handle($event);
    }
}
