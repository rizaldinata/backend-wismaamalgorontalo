<?php

namespace Modules\Notification\Listeners;

use App\Contracts\ConfigProviderInterface;
use App\Events\Jadwal\DPDibayar;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;

class KirimNotifikasiDPDibayar implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function handle(DPDibayar $event): void
    {
        if (! $event->tenantPhone) {
            return;
        }

        if (! $this->settingService->isFeatureEnabled('notif_receipt')) {
            return;
        }

        $pelunasanFormatted = number_format($event->pelunasanAmount, 0, ',', '.');
        $dpFormatted = number_format($event->dpAmount, 0, ',', '.');

        $message = "*KONFIRMASI DP DITERIMA*\n"
            ."Wisma Amal Gorontalo\n\n"
            ."Yth. Bpk/Ibu {$event->tenantName},\n\n"
            ."Pembayaran DP sebesar *Rp {$dpFormatted}* untuk kamar "
            ."*No. {$event->roomNumber}* telah kami terima.\n\n"
            ."Sisa pelunasan: *Rp {$pelunasanFormatted}*\n"
            ."Harap dilunasi sebelum tanggal masuk: *{$event->startDate}*\n\n"
            ."Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::JADWAL_DIBUAT,
            $event->tenantPhone,
            $message
        );
    }
}
