<?php

namespace Modules\Schedule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\ScheduleService;

class AktifkanJadwalYangMulaiHariIni extends Command
{
    protected $signature = 'schedule:aktifkan-hari-ini';

    protected $description = 'Aktifkan jadwal berstatus terkonfirmasi yang start_date-nya sudah tiba hari ini';

    public function __construct(private readonly ScheduleService $scheduleService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $jadwals = Schedule::where('status', ScheduleStatus::TERKONFIRMASI->value)
            ->whereDate('start_date', '<=', today())
            ->get();

        $aktif = 0;
        $gagal = 0;

        foreach ($jadwals as $jadwal) {
            try {
                $this->scheduleService->aktifkanJadwal($jadwal->id);
                $aktif++;

                Log::info('AktifkanJadwalYangMulaiHariIni: jadwal diaktifkan.', [
                    'schedule_id' => $jadwal->id,
                    'start_date' => $jadwal->start_date->toDateString(),
                ]);
            } catch (\Throwable $e) {
                $gagal++;

                Log::error('AktifkanJadwalYangMulaiHariIni: gagal mengaktifkan jadwal.', [
                    'schedule_id' => $jadwal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Selesai: {$aktif} jadwal diaktifkan, {$gagal} gagal.");
    }
}
