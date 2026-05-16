<?php

namespace Modules\Notification\Listeners;

use App\Events\Finance\PembayaranDiterima;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;

class KirimNotifikasiPembayaranDiterima implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SettingService $settingService,
    ) {}

    public function handle(PembayaranDiterima $event): void
    {
        if (! $this->settingService->isFeatureEnabled('feature_whatsapp_receipt')) {
            return;
        }

        $amount = number_format($event->amount, 0, ',', '.');

        $message = "*PEMBAYARAN DITERIMA*\n"
            ."Wisma Amal Gorontalo\n\n"
            ."Yth. Bpk/Ibu {$event->tenantName},\n\n"
            ."Pembayaran sebesar *Rp{$amount}* telah kami terima dan sedang diverifikasi oleh admin.\n\n"
            ."Anda akan mendapat konfirmasi kembali setelah proses verifikasi selesai.\n\n"
            ."Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendNotification(
            NotificationType::PEMBAYARAN_DITERIMA,
            $event->tenantPhone,
            $message
        );
    }
}
