<?php

namespace App\Events\Finance;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PembayaranDiterima
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $paymentId,
        public readonly int $invoiceId,
        public readonly int $scheduleId,
        public readonly float $amount,
        public readonly string $tenantName,
        public readonly string $tenantPhone,
    ) {}
}
