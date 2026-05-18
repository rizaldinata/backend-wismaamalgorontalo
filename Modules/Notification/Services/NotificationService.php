<?php

namespace Modules\Notification\Services;

use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Contracts\WhatsAppProviderInterface;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;
use App\Contracts\ConfigProviderInterface;

readonly class NotificationService
{
    public function __construct(
        private WhatsAppProviderInterface $whatsAppProvider,
        private NotificationRepositoryInterface $repository,
        private ConfigProviderInterface $settingService
    ) {}

    public function sendNotification(NotificationType $type, string $target, string $message): bool
    {
        $isSent = $this->whatsAppProvider->sendMessage($target, $message);

        $status = $isSent ? NotificationStatus::SENT->value : NotificationStatus::FAILED->value;
        $error = $isSent ? null : 'Failed to send via provider';

        $this->repository->logNotification($type, $target, $message, $status, $error);

        return $isSent;
    }

    public function sendReceiptFromPrimitives(
        string $tenantName,
        string $tenantPhone,
        string $invoiceNumber,
        string $roomTitle,
        string $roomNumber,
        string $periode,
        float $amount,
        ?string $pdfLink = null
    ): bool {
        $message = $this->formatReceiptMessage(
            $tenantName,
            $invoiceNumber,
            $roomTitle,
            $roomNumber,
            $periode,
            number_format($amount, 0, ',', '.'),
            $pdfLink
        );

        return $this->sendNotification(NotificationType::PAYMENT_RECEIPT, $tenantPhone, $message);
    }

    public function sendCustomNotification(string $target, string $message): bool
    {
        return $this->sendNotification(NotificationType::MANUAL_BROADCAST, $target, $message);
    }

    private function formatReceiptMessage(
        string $name,
        string $invoiceNo,
        string $roomTitle,
        string $roomNumber,
        string $periode,
        string $amount,
        ?string $pdfLink = null
    ): string {
        $msg = "*BUKTI PEMBAYARAN RESMI*\n"
            ."Wisma Amal Gorontalo\n\n"
            ."Yth. Bpk/Ibu {$name},\n\n"
            ."Kami informasikan bahwa pembayaran Anda telah kami terima dengan rincian sebagai berikut:\n\n"
            ."🧾 *No. Tagihan:* {$invoiceNo}\n"
            ."🚪 *Kamar:* {$roomTitle} (No. {$roomNumber})\n"
            ."⏳ *Periode Sewa:* {$periode}\n"
            ."💰 *Total Bayar:* Rp{$amount}\n";

        if ($pdfLink) {
            $msg .= "\n📥 *Unduh Kwitansi PDF:*\n"
                .$pdfLink."\n"
                ."⚠️ _Link berlaku selama 48 jam. Setelah itu, kwitansi tetap dapat dilihat melalui website atau aplikasi dengan login ke akun Anda._\n";
        }

        $msg .= "\nJika ada pertanyaan, silakan hubungi admin kami.\n\n"
            ."Hormat kami,\n"
            .'*Manajemen Wisma Amal Gorontalo*';

        return $msg;
    }

    public function getLogHistory(int $perPage = 15)
    {
        return $this->repository->getLogsPaginated($perPage);
    }

    public function resendFailedNotification(int $logId): bool
    {
        $log = $this->repository->findById($logId);

        if ($log->status === NotificationStatus::SENT) {
            throw new \DomainException('Notifikasi ini sudah berhasil terkirim sebelumnya.');
        }

        $isSent = $this->whatsAppProvider->sendMessage($log->target_phone, $log->message_body);
        $status = $isSent ? NotificationStatus::SENT->value : NotificationStatus::FAILED->value;
        $error = $isSent ? null : 'Retry failed';

        $this->repository->updateStatus($log, $status, $error);

        return $isSent;
    }
}
