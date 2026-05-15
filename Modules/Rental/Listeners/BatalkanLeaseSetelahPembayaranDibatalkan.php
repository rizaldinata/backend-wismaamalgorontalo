<?php

namespace Modules\Rental\Listeners;

use App\Events\Finance\PembayaranDibatalkan;
use Modules\Rental\Services\RentalService;

class BatalkanLeaseSetelahPembayaranDibatalkan
{
    public function __construct(private readonly RentalService $rentalService) {}

    public function handle(PembayaranDibatalkan $event): void
    {
        $this->rentalService->cancelLease($event->scheduleId);
    }
}
