<?php

namespace Modules\Notification\Listeners;

use App\Events\Jadwal\JadwalSewaSelesai;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;

class KirimNotifikasiJadwalSewaSelesai implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SettingService $settingService,
    ) {}

    public function handle(JadwalSewaSelesai $event): void
    {
        if (! $this->settingService->isFeatureEnabled('feature_whatsapp_receipt')) {
            return;
        }

        $message = "*MASA SEWA SELESAI*\n"
            . "Wisma Amal Gorontalo\n\n"
            . "Yth. Bpk/Ibu {$event->tenantName},\n\n"
            . "Masa sewa Anda untuk kamar *No. {$event->roomNumber}* telah berakhir per {$event->endDate}.\n\n"
            . "Terima kasih telah menjadi penghuni Wisma Amal Gorontalo.\n"
            . "Sampai jumpa kembali!\n\n"
            . "Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::JADWAL_SEWA_SELESAI,
            $event->tenantPhone,
            $message
        );
    }
}
