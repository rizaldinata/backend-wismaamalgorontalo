<?php

namespace Modules\Notification\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Finance\Events\PaymentSettled;
use Modules\Notification\Services\FonnteService;

class SendWhatsAppReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly FonnteService $fonnteService
    ) {}

    public function handle(PaymentSettled $event): void
    {
        $payment = $event->payment;
        $payment->loadMissing(['invoice.lease.resident.user', 'invoice.lease.room']);
        
        $invoice = $payment->invoice;
        $lease = $invoice->lease;
        $room = $lease->room;
        $resident = $lease->resident;
        $user = $resident->user;
        
        $phone = $resident->phone_number;
        $amount = number_format($invoice->amount, 0, ',', '.');
        
        $periode = $lease->start_date->format('d/m/Y') . ' - ' . $lease->end_date->format('d/m/Y');
        
        $message = "*BUKTI PEMBAYARAN RESMI*\n";
        $message .= "Wisma Amal Gorontalo\n\n";
        $message .= "Yth. Bpk/Ibu {$user->name},\n\n";
        $message .= "Kami informasikan bahwa pembayaran Anda telah kami terima dengan rincian sebagai berikut:\n\n";
        $message .= "🧾 *No. Tagihan:* {$invoice->invoice_number}\n";
        $message .= "🚪 *Kamar:* {$room->title} (No. {$room->number})\n";
        $message .= "⏳ *Periode Sewa:* {$periode}\n";
        $message .= "💰 *Total Bayar:* Rp{$amount}\n\n";
        $message .= "Tanda terima resmi dapat Anda lihat melalui akun Anda di aplikasi kami. Jika ada pertanyaan, silakan hubungi admin kami.\n\n";
        $message .= "Hormat kami,\n";
        $message .= "*Manajemen Wisma Amal Gorontalo*";
        
        $this->fonnteService->sendMessage($phone, $message);
    }
}
