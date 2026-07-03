<?php

namespace Modules\Notification\Listeners;

use App\Contracts\ConfigProviderInterface;
use App\Events\Finance\DendaDibuat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;

class KirimNotifikasiDenda implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function handle(DendaDibuat $event): void
    {
        if (! $this->settingService->isFeatureEnabled('notif_receipt')) {
            return;
        }

        $nominal = 'Rp '.number_format($event->amount, 0, ',', '.');

        $message = "*PEMBERITAHUAN DENDA*\n"
            ."Wisma Amal Gorontalo\n\n"
            ."Yth. Bpk/Ibu {$event->tenantName},\n\n"
            ."Anda mendapatkan denda dengan rincian berikut:\n\n"
            ."Nominal  : *{$nominal}*\n"
            ."Alasan   : {$event->reason}\n\n"
            ."Silakan lakukan pembayaran denda melalui aplikasi Wisma Amal Gorontalo.\n"
            ."Jika ada keberatan, harap hubungi manajemen.\n\n"
            ."Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::DENDA_DIBUAT,
            $event->tenantPhone,
            $message,
        );
    }
}
