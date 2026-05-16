<?php

namespace Modules\Notification\Listeners;

use App\Events\Finance\PembayaranDiverifikasi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;

class SendWhatsAppReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SettingService $settingService,
    ) {}

    public function handle(PembayaranDiverifikasi $event): void
    {
        if (! $this->settingService->isFeatureEnabled('feature_whatsapp_receipt')) {
            return;
        }

        $periode = $event->startDate.' - '.$event->endDate;

        $pdfLink = null;
        if ($this->settingService->isFeatureEnabled('feature_whatsapp_pdf_link')) {
            $pdfLink = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'finance.invoice.print',
                now()->addHours(48),
                ['id' => $event->invoiceId]
            );
        }

        $this->notificationService->sendReceiptFromPrimitives(
            tenantName: $event->tenantName,
            tenantPhone: $event->tenantPhone,
            invoiceNumber: $event->invoiceNumber,
            roomTitle: $event->roomTitle,
            roomNumber: $event->roomNumber,
            periode: $periode,
            amount: $event->amount,
            pdfLink: $pdfLink
        );
    }
}
