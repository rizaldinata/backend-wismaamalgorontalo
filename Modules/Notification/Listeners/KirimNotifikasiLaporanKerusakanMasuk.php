<?php

namespace Modules\Notification\Listeners;

use App\Events\Maintenance\LaporanKerusakanMasuk;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use App\Contracts\ConfigProviderInterface;

class KirimNotifikasiLaporanKerusakanMasuk implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function handle(LaporanKerusakanMasuk $event): void
    {
        if (! $this->settingService->isFeatureEnabled('notif_receipt')) {
            return;
        }

        if (empty($event->reporterPhone)) {
            return;
        }

        $roomInfo = $event->roomNumber ? " (Kamar {$event->roomNumber})" : '';

        $message = "*LAPORAN KERUSAKAN DITERIMA*\n"
            . "Wisma Amal Gorontalo\n\n"
            . "Yth. Bpk/Ibu {$event->reporterName},\n\n"
            . "Laporan kerusakan Anda{$roomInfo} telah kami terima dan akan segera ditindaklanjuti.\n\n"
            . "*Deskripsi:* {$event->description}\n\n"
            . "Terima kasih atas laporannya.\n\n"
            . "Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::LAPORAN_KERUSAKAN,
            $event->reporterPhone,
            $message
        );
    }
}
