<?php

namespace Modules\Notification\Console;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Modules\Rental\Models\Lease;
use Modules\Rental\Enums\LeaseStatus;
use Modules\Rental\Enums\RentalType;
use Modules\Notification\Services\NotificationService;

class SendLeaseRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notification:lease-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send WhatsApp reminders for monthly leases that are going to expire in 7, 3, 2, or 0 days.';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Finding active monthly leases going to expire...');
        
        $targetDays = [7, 3, 2, 0];
        $today = Carbon::today();

        $leases = Lease::with(['resident.user', 'room'])
            ->where('status', LeaseStatus::ACTIVE->value)
            ->where('rental_type', RentalType::MONTHLY->value)
            ->get();

        $count = 0;
        foreach ($leases as $lease) {
            // Kita ingin membandingkan sisa hari dari hari ini ke tanggal end_date
            // Pastikan tidak mengirim reminder di luar targetDays (misalnya sudah lewat).
            $diffParams = Carbon::parse($lease->end_date)->startOfDay();
            $daysUntilExpiry = $today->diffInDays($diffParams, false); // false agar bisa negatif jika terlambat

            // Mengubah tipe return yang float menjadi int
            $daysUntilExpiry = (int) round($daysUntilExpiry);

            if (in_array($daysUntilExpiry, $targetDays)) {
                $resident = $lease->resident;
                if (!$resident || !$resident->phone_number) continue;

                $user = $resident->user;
                $phone = $resident->phone_number;
                $room = $lease->room;
                
                $message = "*PENGINGAT PEMBAYARAN SEWA*\n"
                         . "Wisma Amal Gorontalo\n\n"
                         . "Yth. Bpk/Ibu {$user->name},\n\n";
                         
                $roomName = $room->title ?: $room->number;
                
                if ($daysUntilExpiry === 0) {
                    $message .= "Kami informasikan bahwa masa sewa kamar *{$roomName} (No. {$room->number})* Anda habis *HARI INI*.\n\n";
                } else {
                    $message .= "Kami informasikan bahwa masa sewa kamar *{$roomName} (No. {$room->number})* Anda akan habis dalam *{$daysUntilExpiry} hari* (jatuh tempo pada " . Carbon::parse($lease->end_date)->format('d/m/Y') . ").\n\n";
                }
                
                $message .= "Mohon segera lakukan perpanjangan/pembayaran untuk bulan berikutnya jika Anda masih ingin menyewa kamar Anda. Transaksi bisa dilakukan melalui dashboard aplikasi kami.\n"
                          . "Abaikan pesan ini jika Anda sudah berencana tidak memperpanjang atau sudah melakukan pembayaran.\n\n"
                          . "Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

                $notificationService->sendCustomNotification($phone, $message);
                $this->info("Reminder sent to {$user->name} for Room {$room->number}.");
                $count++;
            }
        }
        
        $this->info("Successfully sent $count reminders.");
    }
}
