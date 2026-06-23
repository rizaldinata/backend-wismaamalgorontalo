<?php

namespace Modules\Schedule\Listeners;

use App\Events\Finance\PembayaranDiterima;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Modules\Schedule\Services\ScheduleService;

class AktifkanJadwalSetelahPembayaranDiterima
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {}

    public function handle(PembayaranDiterima $event): void
    {
        if ($event->scheduleId <= 0) {
            return;
        }

        if (in_array($event->invoiceType, ['dp', 'pelunasan'])) {
            return;
        }

        try {
            $schedule = $this->scheduleRepository->findById($event->scheduleId);
        } catch (\Throwable) {
            Log::warning('AktifkanJadwalSetelahPembayaranDiterima: schedule tidak ditemukan.', [
                'schedule_id' => $event->scheduleId,
                'payment_id'  => $event->paymentId,
            ]);

            return;
        }

        // Kasus 1: Jadwal baru (PENDING) — cek apakah start_date sudah tiba
        if ($schedule->status === ScheduleStatus::PENDING) {
            if ($schedule->start_date->gt(today())) {
                // Start date belum tiba → konfirmasi dulu, aktifkan via command harian
                $this->scheduleService->konfirmasiJadwal($schedule->id);

                Log::info('Jadwal dikonfirmasi (start_date belum tiba) setelah pembayaran Midtrans diterima.', [
                    'schedule_id' => $event->scheduleId,
                    'start_date'  => $schedule->start_date->toDateString(),
                ]);
            } else {
                // Start date sudah tiba atau hari ini → aktifkan langsung
                $this->scheduleService->aktifkanJadwal($schedule->id);

                Log::info('Jadwal diaktifkan setelah pembayaran Midtrans diterima.', [
                    'schedule_id' => $event->scheduleId,
                    'payment_id'  => $event->paymentId,
                ]);
            }

            return;
        }

        // Kasus 2: Jadwal sudah ACTIVE → ini pembayaran perpanjangan, update end_date
        if ($schedule->status === ScheduleStatus::ACTIVE && $event->invoiceId > 0) {
            $this->terapkanPerpanjangan($event->scheduleId, $event->periodEnd);

            Log::info('end_date jadwal diperbarui setelah pembayaran perpanjangan Midtrans diterima.', [
                'schedule_id' => $event->scheduleId,
                'invoice_id'  => $event->invoiceId,
                'payment_id'  => $event->paymentId,
            ]);
        }
    }

    private function terapkanPerpanjangan(int $scheduleId, ?string $periodEnd): void
    {
        if (! $periodEnd) {
            return;
        }

        DB::table('room_schedules')
            ->where('id', $scheduleId)
            ->update(['end_date' => $periodEnd, 'updated_at' => now()]);

        DB::table('finance_active_tenants')
            ->where('schedule_id', $scheduleId)
            ->update(['end_date' => $periodEnd, 'updated_at' => now()]);
    }
}
