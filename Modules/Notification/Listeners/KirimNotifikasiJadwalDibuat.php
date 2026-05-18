<?php

namespace Modules\Notification\Listeners;

use App\Events\Jadwal\JadwalDibuat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use App\Contracts\ConfigProviderInterface;

class KirimNotifikasiJadwalDibuat implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function handle(JadwalDibuat $event): void
    {
        if ($event->tipeJadwal !== 'sewa' || ! $event->tenantPhone) {
            return;
        }

        if (! $this->settingService->isFeatureEnabled('feature_whatsapp_receipt')) {
            return;
        }

        $message = "*PENGAJUAN SEWA DITERIMA*\n"
            ."Wisma Amal Gorontalo\n\n"
            ."Yth. Bpk/Ibu {$event->tenantName},\n\n"
            ."Pengajuan sewa Anda untuk kamar *No. {$event->roomNumber}* telah kami terima.\n"
            ."Masa sewa: {$event->startDate} s/d {$event->endDate}\n\n"
            ."Mohon tunggu konfirmasi dari admin kami.\n\n"
            ."Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::JADWAL_DIBUAT,
            $event->tenantPhone,
            $message
        );
    }
}
