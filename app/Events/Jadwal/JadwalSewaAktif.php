<?php

namespace App\Events\Jadwal;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JadwalSewaAktif
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $scheduleId,
        public readonly int $roomId,
        public readonly string $roomNumber,
        public readonly string $tenantName,
        public readonly string $tenantPhone,
        public readonly string $startDate,
        public readonly ?int $userId = null,
        public readonly ?float $roomPrice = null,
        public readonly ?string $tenantEmail = null,
    ) {}
}
