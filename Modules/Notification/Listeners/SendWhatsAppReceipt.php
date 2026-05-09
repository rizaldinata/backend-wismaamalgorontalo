<?php

namespace Modules\Notification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Finance\Events\PaymentSettled;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;

class SendWhatsAppReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SettingService $settingService,
    ) {}

    public function handle(PaymentSettled $event): void
    {
        // Hanya kirim jika fitur notifikasi WA struk diaktifkan admin
        if (!$this->settingService->isFeatureEnabled('feature_whatsapp_receipt')) {
            return;
        }

        $this->notificationService->sendPaymentReceiptMessage($event->payment);
    }
}
