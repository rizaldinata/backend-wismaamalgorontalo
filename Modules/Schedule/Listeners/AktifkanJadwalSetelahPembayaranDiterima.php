<?php

namespace Modules\Schedule\Listeners;

use App\Events\Finance\PembayaranDiterima;
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

        try {
            $schedule = $this->scheduleRepository->findById($event->scheduleId);
        } catch (\Throwable) {
            Log::warning('AktifkanJadwalSetelahPembayaranDiterima: schedule tidak ditemukan.', [
                'schedule_id' => $event->scheduleId,
                'payment_id'  => $event->paymentId,
            ]);

            return;
        }

        if ($schedule->status !== ScheduleStatus::PENDING) {
            return;
        }

        $this->scheduleService->aktifkanJadwal($schedule->id);

        Log::info('Jadwal diaktifkan setelah pembayaran Midtrans diterima.', [
            'schedule_id' => $event->scheduleId,
            'payment_id'  => $event->paymentId,
        ]);
    }
}
