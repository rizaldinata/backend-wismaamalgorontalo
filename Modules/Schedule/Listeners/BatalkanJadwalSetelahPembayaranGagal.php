<?php

namespace Modules\Schedule\Listeners;

use App\Events\Finance\PembayaranDibatalkan;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Modules\Schedule\Services\ScheduleService;

class BatalkanJadwalSetelahPembayaranGagal
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {}

    public function handle(PembayaranDibatalkan $event): void
    {
        // Hanya batalkan jadwal jika pembayaran FAILED (Midtrans expire/cancel)
        // atau REFUNDED — bukan REJECTED (admin tolak manual, user bisa coba lagi)
        if (! in_array($event->paymentStatus, ['failed', 'refunded'])) {
            return;
        }

        if ($event->scheduleId <= 0) {
            return;
        }

        try {
            $schedule = $this->scheduleRepository->findById($event->scheduleId);
        } catch (\Throwable) {
            Log::warning('BatalkanJadwalSetelahPembayaranGagal: schedule tidak ditemukan.', [
                'schedule_id'    => $event->scheduleId,
                'payment_id'     => $event->paymentId,
                'payment_status' => $event->paymentStatus,
            ]);

            return;
        }

        // Jangan batalkan jika sudah di-state terminal
        if (in_array($schedule->status, [ScheduleStatus::CANCELLED, ScheduleStatus::FINISHED])) {
            return;
        }

        // Jika jadwal sudah ACTIVE, berarti ada sewa yang sedang berjalan.
        // Pembayaran yang gagal/direfund adalah pembayaran perpanjangan — jangan batalkan jadwal utama.
        // Kamar baru boleh jadi AVAILABLE jika tidak ada sewa aktif sama sekali.
        if ($schedule->status === ScheduleStatus::ACTIVE) {
            Log::info('Pembayaran gagal/direfund pada jadwal aktif. Jadwal tetap aktif, kamar tetap terisi.', [
                'schedule_id'    => $event->scheduleId,
                'invoice_id'     => $event->invoiceId,
                'payment_status' => $event->paymentStatus,
            ]);

            return;
        }

        // Jika pembayaran pelunasan gagal, jadwal tetap di DP_TERBAYAR — penghuni bisa coba lagi
        if ($schedule->status === ScheduleStatus::DP_TERBAYAR) {
            if ($event->invoiceType === 'pelunasan') {
                Log::info('Pembayaran pelunasan gagal. Jadwal tetap dp_terbayar.', [
                    'schedule_id'    => $event->scheduleId,
                    'invoice_id'     => $event->invoiceId,
                    'payment_status' => $event->paymentStatus,
                ]);

                return;
            }
        }

        $this->scheduleService->batalkanJadwal($schedule->id);

        Log::info('Jadwal dibatalkan setelah pembayaran gagal/direfund.', [
            'schedule_id'    => $event->scheduleId,
            'payment_id'     => $event->paymentId,
            'payment_status' => $event->paymentStatus,
        ]);
    }
}
