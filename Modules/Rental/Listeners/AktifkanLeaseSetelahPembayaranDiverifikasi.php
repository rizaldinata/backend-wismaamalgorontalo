<?php

namespace Modules\Rental\Listeners;

use App\Events\Finance\PembayaranDiverifikasi;
use Modules\Rental\Services\RentalService;

class AktifkanLeaseSetelahPembayaranDiverifikasi
{
    public function __construct(private readonly RentalService $rentalService) {}

    public function handle(PembayaranDiverifikasi $event): void
    {
        $this->rentalService->activateLease($event->scheduleId);
    }
}
