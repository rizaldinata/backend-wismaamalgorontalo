<?php

namespace Modules\Notification\Services;

use Modules\Finance\Models\Payment; 
use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Contracts\WhatsAppProviderInterface;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Enums\NotificationType;

readonly class NotificationService
{
    public function __construct(
        private WhatsAppProviderInterface $whatsAppProvider,
        private NotificationRepositoryInterface $repository
    ) {}

    public function sendPaymentReceiptMessage(Payment $payment): bool
    {
        $payment->loadMissing(['invoice.lease.resident.user', 'invoice.lease.room']);
        
        $invoice = $payment->invoice;
        $lease = $invoice->lease;
        $room = $lease->room;
        $resident = $lease->resident;
        $user = $resident->user;
        
        $phone = $resident->phone_number;
        $amount = number_format($invoice->amount, 0, ',', '.');
        $periode = collect([$lease->start_date, $lease->end_date])
            ->map(fn($date) => $date->format('d/m/Y'))
            ->join(' - ');
        
        $message = $this->formatReceiptMessage($user->name, $invoice->invoice_number, $room->title, $room->number, $periode, $amount);
        
        return $this->sendNotification(NotificationType::PAYMENT_RECEIPT, $phone, $message);
    }

    public function sendNotification(NotificationType $type, string $target, string $message): bool
    {
        $isSent = $this->whatsAppProvider->sendMessage($target, $message);
        
        $status = $isSent ? NotificationStatus::SENT->value : NotificationStatus::FAILED->value;
        $error  = $isSent ? null : 'Failed to send via provider';
        
        $this->repository->logNotification($type, $target, $message, $status, $error);

        return $isSent;
    }

    public function sendCustomNotification(string $target, string $message): bool
    {
        return $this->sendNotification(NotificationType::MANUAL_BROADCAST, $target, $message);
    }

    private function formatReceiptMessage(string $name, string $invoiceNo, string $roomTitle, string $roomNumber, string $periode, string $amount): string
    {
        return "*BUKTI PEMBAYARAN RESMI*\n"
            . "Wisma Amal Gorontalo\n\n"
            . "Yth. Bpk/Ibu {$name},\n\n"
            . "Kami informasikan bahwa pembayaran Anda telah kami terima dengan rincian sebagai berikut:\n\n"
            . "🧾 *No. Tagihan:* {$invoiceNo}\n"
            . "🚪 *Kamar:* {$roomTitle} (No. {$roomNumber})\n"
            . "⏳ *Periode Sewa:* {$periode}\n"
            . "💰 *Total Bayar:* Rp{$amount}\n\n"
            . "Tanda terima resmi dapat Anda lihat melalui akun Anda di aplikasi kami. Jika ada pertanyaan, silakan hubungi admin kami.\n\n"
            . "Hormat kami,\n"
            . "*Manajemen Wisma Amal Gorontalo*";
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
        $error  = $isSent ? null : 'Retry failed';

        $this->repository->updateStatus($log, $status, $error);
        return $isSent;
    }
}