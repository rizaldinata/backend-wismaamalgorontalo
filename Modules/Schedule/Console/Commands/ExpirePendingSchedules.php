<?php

namespace Modules\Schedule\Console\Commands;

use App\Contracts\PaymentStatusCheckerInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\ScheduleService;

class ExpirePendingSchedules extends Command
{
    protected $signature = 'schedule:expire-pending';

    protected $description = 'Batalkan jadwal sewa PENDING yang sudah melebihi batas waktu pembayaran (15 menit) dan belum ada pembayaran aktif';

    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly PaymentStatusCheckerInterface $paymentStatusChecker,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $expiredAt = now()->subMinutes(15);

        $expiredSchedules = Schedule::where('status', ScheduleStatus::PENDING->value)
            ->where('type', ScheduleType::SEWA->value)
            ->where('created_at', '<', $expiredAt)
            ->get();

        $cancelled = 0;
        $skipped = 0;

        foreach ($expiredSchedules as $schedule) {
            $hasActivePayment = $this->paymentStatusChecker->hasActivePaymentForSchedule($schedule->id);

            if ($hasActivePayment) {
                $skipped++;
                continue;
            }

            try {
                $this->scheduleService->batalkanJadwal($schedule->id);
                $cancelled++;

                Log::info('ExpirePendingSchedules: jadwal dibatalkan karena melewati batas waktu.', [
                    'schedule_id' => $schedule->id,
                    'room_id' => $schedule->room_id,
                    'created_at' => $schedule->created_at,
                ]);
            } catch (\Throwable $e) {
                Log::error('ExpirePendingSchedules: gagal membatalkan jadwal.', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Selesai: {$cancelled} jadwal dibatalkan, {$skipped} dilewati (ada pembayaran aktif).");
    }
}
