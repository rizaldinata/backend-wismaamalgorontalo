<?php

namespace Modules\Schedule\Listeners;

use App\Events\Finance\PembayaranDiterima;
use App\Events\Finance\PembayaranDiverifikasi;
use App\Events\Jadwal\DPDibayar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;

class TandaiDPTerbayarSetelahPembayaranDP
{
    public function __construct(
        private readonly ScheduleRepositoryInterface $scheduleRepository,
    ) {}

    public function handle(PembayaranDiverifikasi|PembayaranDiterima $event): void
    {
        if ($event->invoiceId <= 0) {
            return;
        }

        if ($event->invoiceType !== 'dp') {
            return;
        }

        if ($event->scheduleId <= 0) {
            return;
        }

        try {
            $schedule = $this->scheduleRepository->findById($event->scheduleId);
        } catch (\Throwable) {
            Log::warning('TandaiDPTerbayarSetelahPembayaranDP: schedule tidak ditemukan.', [
                'schedule_id' => $event->scheduleId,
                'invoice_id'  => $event->invoiceId,
            ]);

            return;
        }

        if ($schedule->status !== ScheduleStatus::PENDING) {
            return;
        }

        DB::table('room_schedules')
            ->where('id', $schedule->id)
            ->update([
                'status'     => ScheduleStatus::DP_TERBAYAR->value,
                'dp_paid_at' => now(),
                'updated_at' => now(),
            ]);

        $schedule->refresh();

        $pelunasanAmount = (float) $schedule->agreed_price - (float) $schedule->dp_amount;

        $roomNumberSnapshot = $event->roomNumber ?? $schedule->room->number ?? '';
        $periodStart = $event instanceof PembayaranDiverifikasi
            ? ($event->startDate ?: $schedule->start_date->toDateString())
            : ($event->periodStart ?? $schedule->start_date->toDateString());
        $periodEnd = $event instanceof PembayaranDiverifikasi
            ? ($event->endDate ?: $schedule->end_date->toDateString())
            : ($event->periodEnd ?? $schedule->end_date->toDateString());

        event(new DPDibayar(
            scheduleId:         $schedule->id,
            roomNumber:         $schedule->room->number ?? '',
            tenantName:         $schedule->tenant_name ?? '',
            tenantPhone:        $schedule->tenant_phone ?? '',
            dpAmount:           (float) $schedule->dp_amount,
            pelunasanAmount:    $pelunasanAmount,
            startDate:          $schedule->start_date->toDateString(),
            endDate:            $schedule->end_date->toDateString(),
            roomNumberSnapshot: $roomNumberSnapshot,
            periodStart:        $periodStart,
            periodEnd:          $periodEnd,
            tenantUserId:       $schedule->tenant_user_id,
            dpInvoiceId:        $event->invoiceId,
        ));

        Log::info('TandaiDPTerbayarSetelahPembayaranDP: jadwal ditandai DP Terbayar.', [
            'schedule_id'     => $schedule->id,
            'dp_amount'       => $schedule->dp_amount,
            'pelunasan_amount' => $pelunasanAmount,
        ]);
    }
}
