<?php

namespace Modules\Rental\Listeners;

use App\Events\Finance\PembayaranDiterima;
use Modules\Rental\Services\RentalService;

class AktifkanLeaseSetelahPembayaranDiterima
{
    public function __construct(private readonly RentalService $rentalService) {}

    public function handle(PembayaranDiterima $event): void
    {
        $this->rentalService->activateLease($event->scheduleId);
    }
}
