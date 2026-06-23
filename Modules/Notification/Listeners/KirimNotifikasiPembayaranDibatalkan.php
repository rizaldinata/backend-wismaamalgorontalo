<?php

namespace Modules\Notification\Listeners;

use App\Events\Finance\PembayaranDibatalkan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use App\Contracts\ConfigProviderInterface;

class KirimNotifikasiPembayaranDibatalkan implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function handle(PembayaranDibatalkan $event): void
    {
        if (! $this->settingService->isFeatureEnabled('notif_receipt')) {
            return;
        }

        if (empty($event->tenantPhone)) {
            return;
        }

        $amount = $event->amount !== null
            ? 'Rp' . number_format($event->amount, 0, ',', '.')
            : '';

        $amountLine = $amount ? "sebesar *{$amount}* " : '';

        $message = "*PEMBAYARAN TIDAK BERHASIL*\n"
            . "Wisma Amal Gorontalo\n\n"
            . "Yth. Bpk/Ibu {$event->tenantName},\n\n"
            . "Pembayaran {$amountLine}Anda tidak dapat diproses atau telah dibatalkan.\n\n"
            . "Mohon hubungi admin atau lakukan pembayaran ulang melalui aplikasi.\n\n"
            . "Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::PEMBAYARAN_DIBATALKAN,
            $event->tenantPhone,
            $message
        );
    }
}
