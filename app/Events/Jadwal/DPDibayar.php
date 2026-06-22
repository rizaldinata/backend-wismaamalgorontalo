<?php

namespace App\Events\Jadwal;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DPDibayar
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int    $scheduleId,
        public readonly string $roomNumber,
        public readonly string $tenantName,
        public readonly string $tenantPhone,
        public readonly float  $dpAmount,
        public readonly float  $pelunasanAmount,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $roomNumberSnapshot,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly ?int   $tenantUserId = null,
        public readonly ?int   $dpInvoiceId = null,
    ) {}
}
