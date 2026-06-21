<?php

namespace Modules\Schedule\Listeners;

use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use Modules\Schedule\Services\ScheduleService;

class AktifkanJadwalSetelahPelunasan
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {}

    public function handle(PembayaranDiverifikasi|PembayaranDiterima $event): void
    {
        if ($event->invoiceId <= 0 || $event->scheduleId <= 0) {
            return;
        }

        $invoiceType = DB::table('invoices')->where('id', $event->invoiceId)->value('type');

        if ($invoiceType !== 'pelunasan') {
            return;
        }

        try {
            $schedule = $this->scheduleRepository->findById($event->scheduleId);
        } catch (\Throwable) {
            Log::warning('AktifkanJadwalSetelahPelunasan: schedule tidak ditemukan.', [
                'schedule_id' => $event->scheduleId,
                'invoice_id'  => $event->invoiceId,
            ]);

            return;
        }

        if ($schedule->status !== ScheduleStatus::DP_TERBAYAR) {
            Log::warning('AktifkanJadwalSetelahPelunasan: status jadwal bukan dp_terbayar, dilewati.', [
                'schedule_id' => $schedule->id,
                'status'      => $schedule->status->value,
            ]);

            return;
        }

        if ($schedule->start_date->gt(today())) {
            // Start date belum tiba → konfirmasi dulu, aktifkan via command harian
            $this->scheduleService->konfirmasiJadwal($schedule->id);

            Log::info('Jadwal dikonfirmasi (start_date belum tiba) setelah pelunasan dibayar.', [
                'schedule_id' => $schedule->id,
                'start_date'  => $schedule->start_date->toDateString(),
            ]);
        } else {
            // Start date sudah tiba atau hari ini → aktifkan langsung
            $this->scheduleService->aktifkanJadwalDariDP($schedule->id);

            Log::info('Jadwal diaktifkan setelah pelunasan dibayar.', [
                'schedule_id' => $schedule->id,
                'invoice_id'  => $event->invoiceId,
            ]);
        }
    }
}
