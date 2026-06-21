<?php

namespace Modules\Schedule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\ScheduleService;

class FinishExpiredSchedules extends Command
{
    protected $signature = 'schedule:finish-expired';

    protected $description = 'Selesaikan jadwal sewa ACTIVE yang masa sewanya sudah berakhir dan tidak ada invoice perpanjangan aktif';

    public function __construct(private readonly ScheduleService $scheduleService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $expiredSchedules = Schedule::where('status', ScheduleStatus::ACTIVE->value)
            ->where('type', ScheduleType::SEWA->value)
            ->where('end_date', '<', now()->toDateString())
            ->get();

        $finished = 0;
        $skipped  = 0;

        foreach ($expiredSchedules as $schedule) {
            // Skip jika ada invoice perpanjangan yang masih dalam window pembayaran 15 menit.
            // period_start > end_date adalah penanda invoice perpanjangan (bukan invoice sewa reguler).
            $hasPendingExtension = DB::table('invoices')
                ->where('schedule_id', $schedule->id)
                ->where('period_start', '>', $schedule->end_date)
                ->where('payment_expires_at', '>', now())
                ->exists();

            if ($hasPendingExtension) {
                $skipped++;
                continue;
            }

            try {
                $this->scheduleService->selesaikanJadwal($schedule->id);
                $finished++;

                Log::info('FinishExpiredSchedules: jadwal diselesaikan otomatis.', [
                    'schedule_id' => $schedule->id,
                    'room_id'     => $schedule->room_id,
                    'end_date'    => $schedule->end_date,
                ]);
            } catch (\Throwable $e) {
                Log::error('FinishExpiredSchedules: gagal menyelesaikan jadwal.', [
                    'schedule_id' => $schedule->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->info("Selesai: {$finished} jadwal diselesaikan, {$skipped} dilewati (ada invoice perpanjangan aktif).");
    }
}
