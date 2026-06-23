<?php

namespace Modules\Guest\Console;

use Illuminate\Console\Command;
use Modules\Guest\Models\Guest;
use Modules\Guest\Services\GuestService;

class NotifyGuestStayEndedCommand extends Command
{
    protected $signature = 'guest:notify-stay-ended';
    protected $description = 'Log notifications for guests whose stay has ended.';

    public function __construct(
        private readonly GuestService $guestService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $processed = 0;

        Guest::query()
            ->whereNotNull('check_out_at')
            ->where('check_out_at', '<=', now())
            ->whereNull('stay_completed_notified_at')
            ->with(['lease.resident.user', 'lease.room'])
            ->chunkById(100, function ($guests) use (&$processed) {
                foreach ($guests as $guest) {
                    $this->guestService->markGuestStayEnded($guest, $guest->check_out_at ?? now());
                    $processed++;
                }
            });

        $this->info("Logged {$processed} stay-ended notifications.");

        return self::SUCCESS;
    }

}
