<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Models\User;
use Modules\Guest\Models\GuestActiveContext;
use Modules\Schedule\Models\Schedule;

class FixGuestContext extends Command
{
    protected $signature = 'app:fix-guest-context';

    protected $description = 'Fix Guest Active Contexts for Seeder data';

    public function handle()
    {
        $schedules = Schedule::where('status', 'active')->get();
        $count = 0;

        foreach ($schedules as $schedule) {
            if (! $schedule->tenant_user_id) {
                continue;
            }

            $user = User::find($schedule->tenant_user_id);
            if (! $user) {
                continue;
            }

            GuestActiveContext::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'schedule_id' => $schedule->id,
                    'room_id' => $schedule->room_id,
                    'room_price' => $schedule->agreed_price ?? 0,
                    'tenant_name' => $schedule->tenant_name,
                    'tenant_email' => $user->email,
                    'tenant_phone' => $schedule->tenant_phone,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        $this->info("Successfully generated GuestActiveContext for $count active schedules.");
    }
}
