<?php

namespace Modules\Schedule\Listeners;

use App\Events\Finance\PembayaranDibatalkan;
use Illuminate\Support\Facades\DB;
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

        // Jika jadwal sudah aktif dan pembayaran gagal (bukan refund), ini adalah
        // pembayaran perpanjang sewa yang expired/dibatalkan — jangan batalkan jadwal,
        // cukup rollback end_date ke sebelum perpanjangan agar tenant tetap aktif.
        if ($schedule->status === ScheduleStatus::ACTIVE && $event->paymentStatus === 'failed') {
            $this->rollbackExtensionEndDate($event->invoiceId, $event->scheduleId);
            Log::info('Perpanjangan sewa gagal, end_date di-rollback. Jadwal tetap aktif.', [
                'schedule_id' => $event->scheduleId,
                'invoice_id'  => $event->invoiceId,
            ]);

            return;
        }

        $this->scheduleService->batalkanJadwal($schedule->id);

        Log::info('Jadwal dibatalkan setelah pembayaran gagal/direfund.', [
            'schedule_id'    => $event->scheduleId,
            'payment_id'     => $event->paymentId,
            'payment_status' => $event->paymentStatus,
        ]);
    }

    // Rollback end_date ke sebelum perpanjangan menggunakan period_start dari invoice.
    // period_start adalah hari pertama periode perpanjangan, sehingga end_date asli
    // adalah period_start - 1 hari.
    // Menggunakan DB::table agar tidak ada ketergantungan langsung ke modul Finance.
    private function rollbackExtensionEndDate(int $invoiceId, int $scheduleId): void
    {
        $periodStart = DB::table('invoices')
            ->where('id', $invoiceId)
            ->value('period_start');

        if (! $periodStart) {
            return;
        }

        $originalEndDate = \Carbon\Carbon::parse($periodStart)->subDay()->toDateString();

        DB::table('room_schedules')
            ->where('id', $scheduleId)
            ->update(['end_date' => $originalEndDate, 'updated_at' => now()]);

        DB::table('finance_active_tenants')
            ->where('schedule_id', $scheduleId)
            ->update(['end_date' => $originalEndDate, 'updated_at' => now()]);
    }
}
