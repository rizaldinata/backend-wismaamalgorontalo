<?php

namespace Modules\Notification\Listeners;

use App\Events\Jadwal\JadwalSewaAktif;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;

class KirimNotifikasiJadwalSewaAktif implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SettingService $settingService,
    ) {}

    public function handle(JadwalSewaAktif $event): void
    {
        if (! $this->settingService->isFeatureEnabled('feature_whatsapp_receipt')) {
            return;
        }

        $message = "*SEWA KAMAR AKTIF*\n"
            ."Wisma Amal Gorontalo\n\n"
            ."Yth. Bpk/Ibu {$event->tenantName},\n\n"
            ."Selamat datang! Sewa kamar *No. {$event->roomNumber}* Anda kini telah aktif.\n"
            ."Tanggal mulai: {$event->startDate}\n\n"
            ."Jika ada keperluan, silakan hubungi admin kami.\n\n"
            ."Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::JADWAL_SEWA_AKTIF,
            $event->tenantPhone,
            $message
        );
    }
}
