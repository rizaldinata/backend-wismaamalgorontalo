<?php

namespace Modules\Schedule\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrasiDataResidentKeJadwal extends Command
{
    protected $signature = 'schedule:verifikasi-data-penghuni
                            {--dry-run : Tampilkan statistik tanpa mengeksekusi perubahan}
                            {--fix : Isi ulang data penghuni yang kosong dari tabel leases/residents}';

    protected $description = 'Verifikasi dan perbaiki data penghuni di tabel room_schedules';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $shouldFix = $this->option('fix');

        $total    = DB::table('room_schedules')->where('type', 'sewa')->count();
        $complete = DB::table('room_schedules')
            ->where('type', 'sewa')
            ->whereNotNull('tenant_name')
            ->whereNotNull('tenant_phone')
            ->count();
        $incomplete = $total - $complete;

        $this->info("Total jadwal tipe sewa : {$total}");
        $this->info("Data penghuni lengkap  : {$complete}");
        $this->warn("Data penghuni tidak lengkap: {$incomplete}");

        if ($incomplete === 0) {
            $this->info('Semua data penghuni sudah lengkap. Tidak ada yang perlu diperbaiki.');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn('[DRY RUN] Tidak ada perubahan yang disimpan.');

            DB::table('room_schedules')
                ->where('type', 'sewa')
                ->where(function ($q) {
                    $q->whereNull('tenant_name')->orWhereNull('tenant_phone');
                })
                ->orderBy('id')
                ->limit(10)
                ->each(function ($schedule) {
                    $this->line("  schedule #{$schedule->id} (legacy_lease_id: {$schedule->legacy_lease_id}) — data penghuni kosong");
                });

            return Command::SUCCESS;
        }

        if (!$shouldFix) {
            $this->warn('Gunakan --fix untuk mengisi data penghuni yang kosong, atau --dry-run untuk pratinjau.');
            return Command::SUCCESS;
        }

        $fixed  = 0;
        $failed = 0;

        DB::table('room_schedules')
            ->where('type', 'sewa')
            ->where(function ($q) {
                $q->whereNull('tenant_name')->orWhereNull('tenant_phone');
            })
            ->whereNotNull('legacy_lease_id')
            ->orderBy('id')
            ->chunk(100, function ($schedules) use (&$fixed, &$failed) {
                foreach ($schedules as $schedule) {
                    $lease    = DB::table('leases')->where('id', $schedule->legacy_lease_id)->first();
                    $resident = $lease ? DB::table('residents')->where('id', $lease->resident_id)->first() : null;
                    $user     = $resident ? DB::table('users')->where('id', $resident->user_id)->first() : null;

                    if (!$resident || !$user) {
                        $this->warn("  schedule #{$schedule->id}: resident/user tidak ditemukan, dilewati.");
                        $failed++;
                        continue;
                    }

                    DB::table('room_schedules')->where('id', $schedule->id)->update([
                        'tenant_user_id'  => $resident->user_id,
                        'tenant_name'     => $user->name,
                        'tenant_id_number'=> $resident->id_card_number,
                        'tenant_phone'    => $resident->phone_number,
                        'tenant_id_photo' => $resident->ktp_photo_path ?? null,
                        'updated_at'      => now(),
                    ]);

                    $fixed++;
                }
            });

        $this->newLine();
        $this->info("Hasil perbaikan:");
        $this->table(['Status', 'Jumlah'], [
            ['Berhasil diperbaiki', $fixed],
            ['Gagal / dilewati',   $failed],
        ]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
