<?php

namespace Modules\Room\Listeners;

use App\Events\Jadwal\JadwalSewaAktif;
use Modules\Room\Contracts\RoomAvailabilityService;

class TandaiKamarTerisiSetelahJadwalAktif
{
    public function __construct(
        private readonly RoomAvailabilityService $roomService,
    ) {}

    public function handle(JadwalSewaAktif $event): void
    {
        $this->roomService->markAsOccupied($event->roomId);
    }
}
