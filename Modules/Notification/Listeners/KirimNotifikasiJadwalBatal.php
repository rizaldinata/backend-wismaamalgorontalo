<?php

namespace Modules\Notification\Listeners;

use App\Events\Jadwal\JadwalBatal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use App\Contracts\ConfigProviderInterface;

class KirimNotifikasiJadwalBatal implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function handle(JadwalBatal $event): void
    {
        if (! $event->tenantPhone) {
            return;
        }

        if (! $this->settingService->isFeatureEnabled('feature_whatsapp_receipt')) {
            return;
        }

        $message = "*SEWA DIBATALKAN*\n"
            ."Wisma Amal Gorontalo\n\n"
            ."Yth. Bpk/Ibu {$event->tenantName},\n\n"
            ."Pengajuan sewa Anda untuk kamar *No. {$event->roomNumber}* telah dibatalkan.\n\n"
            ."Jika ini bukan permintaan Anda atau ada pertanyaan, silakan hubungi admin kami.\n\n"
            ."Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::JADWAL_BATAL,
            $event->tenantPhone,
            $message
        );
    }
}
