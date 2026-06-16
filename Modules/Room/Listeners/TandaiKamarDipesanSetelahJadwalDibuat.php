<?php

namespace Modules\Room\Listeners;

use App\Events\Jadwal\JadwalDibuat;
use Modules\Room\Contracts\RoomAvailabilityService;

class TandaiKamarDipesanSetelahJadwalDibuat
{
    public function __construct(
        private readonly RoomAvailabilityService $roomService,
    ) {}

    public function handle(JadwalDibuat $event): void
    {
        if ($event->tipeJadwal !== 'sewa') {
            return;
        }

        $this->roomService->markAsReserved($event->roomId);
    }
}
