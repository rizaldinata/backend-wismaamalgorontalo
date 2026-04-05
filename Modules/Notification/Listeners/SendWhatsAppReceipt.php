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
        $payment->loadMissing('invoice.lease.resident.user');
        
        $resident = $payment->invoice->lease->resident;
        $user = $resident->user;
        $phone = $resident->phone_number;
        $amount = number_format($payment->invoice->amount, 0, ',', '.');
        
        $message = "Halo {$user->name},\n\nTerima kasih, pembayaran sejumlah Rp{$amount} untuk tagihan {$payment->invoice->invoice_number} telah berhasil.\n\nSebuah tanda terima resmi telah dikirim ke akun Anda.";
        
        $this->fonnteService->sendMessage($phone, $message);
    }
}
