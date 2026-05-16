<?php

namespace Modules\Schedule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Enums\ScheduleStatus;

class MigrasiDataRentalKeJadwal extends Command
{
    protected $signature = 'schedule:migrasi-dari-rental
                            {--dry-run : Tampilkan rencana migrasi tanpa mengeksekusi}
                            {--skip-existing : Lewati lease yang sudah punya room_schedule}';

    protected $description = 'Salin data dari tabel leases ke room_schedules';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $skipExisting = $this->option('skip-existing');

        $leaseCount  = DB::table('leases')->whereNull('deleted_at')->count();
        $existingMap = DB::table('room_schedules')
            ->whereNotNull('legacy_lease_id')
            ->pluck('legacy_lease_id')
            ->flip();

        $this->info("Ditemukan {$leaseCount} lease aktif.");
        $this->info("Sudah ada " . $existingMap->count() . " room_schedule dari lease sebelumnya.");

        if ($isDryRun) {
            $this->warn('[DRY RUN] Tidak ada perubahan yang disimpan.');
        }

        $migrated = 0;
        $skipped  = 0;
        $failed   = 0;

        DB::table('leases')->whereNull('deleted_at')->orderBy('id')->chunk(100, function ($leases) use (
            $isDryRun, $skipExisting, $existingMap, &$migrated, &$skipped, &$failed
        ) {
            foreach ($leases as $lease) {
                if ($skipExisting && $existingMap->has($lease->id)) {
                    $skipped++;
                    continue;
                }

                $status = $this->mapStatus($lease->status);
                $resident = DB::table('residents')->where('id', $lease->resident_id)->first();
                $user     = $resident ? DB::table('users')->where('id', $resident->user_id)->first() : null;

                $scheduleData = [
                    'legacy_lease_id' => $lease->id,
                    'room_id'         => $lease->room_id,
                    'type'            => 'sewa',
                    'status'          => $status,
                    'start_date'      => $lease->start_date,
                    'end_date'        => $lease->end_date,
                    'created_by'      => $resident?->user_id,
                    'tenant_user_id'  => $resident?->user_id,
                    'tenant_name'     => $user?->name,
                    'tenant_id_number'=> $resident?->id_card_number,
                    'tenant_phone'    => $resident?->phone_number,
                    'tenant_id_photo' => $resident?->ktp_photo_path,
                    'agreed_price'    => DB::table('invoices')
                        ->where('lease_id', $lease->id)
                        ->orderBy('id')
                        ->value('amount'),
                    'activated_at'    => $lease->status === 'active' ? $lease->created_at : null,
                    'finished_at'     => $lease->finished_at,
                    'created_at'      => $lease->created_at,
                    'updated_at'      => $lease->updated_at,
                ];

                if (!$isDryRun) {
                    try {
                        DB::transaction(function () use ($scheduleData, $lease) {
                            $scheduleId = DB::table('room_schedules')->insertGetId($scheduleData);

                            // Tautkan invoice yang ada ke schedule baru ini
                            DB::table('invoices')
                                ->where('lease_id', $lease->id)
                                ->update(['schedule_id' => $scheduleId]);
                        });
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("Gagal migrasi lease #{$lease->id}: " . $e->getMessage());
                        $failed++;
                    }
                } else {
                    $this->line("  [DRY] lease #{$lease->id} → room_schedule (status: {$status}, tenant: {$user?->name})");
                    $migrated++;
                }
            }
        });

        $this->newLine();
        $this->info("Hasil migrasi:");
        $this->table(['Status', 'Jumlah'], [
            ['Berhasil dimigrasikan', $migrated],
            ['Dilewati (sudah ada)', $skipped],
            ['Gagal', $failed],
        ]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function mapStatus(string $leaseStatus): string
    {
        return match ($leaseStatus) {
            'pending'   => ScheduleStatus::PENDING->value,
            'active'    => ScheduleStatus::ACTIVE->value,
            'finished'  => ScheduleStatus::FINISHED->value,
            'cancelled' => ScheduleStatus::CANCELLED->value,
            default     => ScheduleStatus::CANCELLED->value,
        };
    }
}
