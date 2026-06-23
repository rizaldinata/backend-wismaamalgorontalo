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
        public readonly ?string $tenantName = null,
        public readonly ?string $tenantPhone = null,
        public readonly ?float $amount = null,
        public readonly ?string $paymentStatus = null,
        public readonly string $invoiceType = 'sewa',
    ) {}
}
