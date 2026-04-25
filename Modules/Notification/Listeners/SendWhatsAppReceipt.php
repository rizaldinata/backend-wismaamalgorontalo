<?php

namespace Modules\Notification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Finance\Events\PaymentSettled;
use Modules\Notification\Services\NotificationService;

class SendWhatsAppReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(PaymentSettled $event): void
    {
        $this->notificationService->sendPaymentReceiptMessage($event->payment);
    }
}
