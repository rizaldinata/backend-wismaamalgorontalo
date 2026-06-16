<?php

namespace Modules\Room\Listeners;

use App\Events\Jadwal\JadwalBatal;
use App\Events\Jadwal\JadwalSewaSelesai;
use Modules\Room\Contracts\RoomAvailabilityService;

class TandaiKamarTersediaSetelahJadwalSelesai
{
    public function __construct(
        private readonly RoomAvailabilityService $roomService,
    ) {}

    public function handle(JadwalSewaSelesai|JadwalBatal $event): void
    {
        $this->roomService->markAsAvailable($event->roomId);
    }
}
