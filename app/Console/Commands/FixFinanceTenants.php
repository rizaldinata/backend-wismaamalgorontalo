<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixFinanceTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-finance-tenants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = \Modules\Schedule\Models\Schedule::with('room')->where('status', 'active')->get();
        $count = 0;

        foreach ($schedules as $schedule) {
            if (!$schedule->tenant_user_id) continue;

            \Illuminate\Support\Facades\DB::table('finance_active_tenants')->updateOrInsert(
                ['schedule_id' => $schedule->id],
                [
                    'user_id' => $schedule->tenant_user_id,
                    'room_number' => $schedule->room->number ?? '-',
                    'tenant_name' => $schedule->tenant_name,
                    'tenant_phone' => $schedule->tenant_phone,
                    'start_date' => $schedule->start_date,
                    'end_date' => $schedule->end_date,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Successfully populated finance_active_tenants for {$count} active schedules.");
    }
}
