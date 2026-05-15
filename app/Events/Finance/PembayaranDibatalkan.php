<?php

namespace App\Events\Finance;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PembayaranDibatalkan
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $paymentId,
        public readonly int $invoiceId,
        public readonly int $scheduleId,
    ) {}
}
