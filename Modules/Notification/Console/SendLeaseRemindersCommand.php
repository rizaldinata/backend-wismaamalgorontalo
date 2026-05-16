<?php

namespace Modules\Notification\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Services\NotificationService;

class SendLeaseRemindersCommand extends Command
{
    protected $signature = 'notification:lease-reminders';

    protected $description = 'Send WhatsApp reminders for active sewa schedules expiring in 7, 3, 2, or 0 days.';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Finding active sewa schedules going to expire...');

        $targetDays = [7, 3, 2, 0];
        $today      = Carbon::today();

        $schedules = DB::table('room_schedules')
            ->join('rooms', 'rooms.id', '=', 'room_schedules.room_id')
            ->where('room_schedules.status', 'active')
            ->where('room_schedules.type', 'sewa')
            ->select(
                'room_schedules.id',
                'room_schedules.end_date',
                'room_schedules.tenant_name',
                'room_schedules.tenant_phone',
                'rooms.number as room_number',
                'rooms.title as room_title',
            )
            ->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            if (!$schedule->tenant_phone) {
                continue;
            }

            $endDate         = Carbon::parse($schedule->end_date)->startOfDay();
            $daysUntilExpiry = (int) round($today->diffInDays($endDate, false));

            if (!in_array($daysUntilExpiry, $targetDays)) {
                continue;
            }

            $roomName = $schedule->room_title ?: $schedule->room_number;

            if ($daysUntilExpiry === 0) {
                $body = "Kami informasikan bahwa masa sewa kamar *{$roomName} (No. {$schedule->room_number})* Anda habis *HARI INI*.\n\n";
            } else {
                $endFormatted = Carbon::parse($schedule->end_date)->format('d/m/Y');
                $body = "Kami informasikan bahwa masa sewa kamar *{$roomName} (No. {$schedule->room_number})* Anda akan habis dalam *{$daysUntilExpiry} hari* (jatuh tempo pada {$endFormatted}).\n\n";
            }

            $message = "*PENGINGAT PEMBAYARAN SEWA*\n"
                     . "Wisma Amal Gorontalo\n\n"
                     . "Yth. Bpk/Ibu {$schedule->tenant_name},\n\n"
                     . $body
                     . "Mohon segera lakukan perpanjangan/pembayaran untuk bulan berikutnya jika Anda masih ingin menyewa kamar Anda. Transaksi bisa dilakukan melalui dashboard aplikasi kami.\n"
                     . "Abaikan pesan ini jika Anda sudah berencana tidak memperpanjang atau sudah melakukan pembayaran.\n\n"
                     . "Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

            $notificationService->sendCustomNotification($schedule->tenant_phone, $message);
            $this->info("Reminder sent to {$schedule->tenant_name} for Room {$schedule->room_number}.");
            $count++;
        }

        $this->info("Successfully sent {$count} reminders.");

        return Command::SUCCESS;
    }
}
