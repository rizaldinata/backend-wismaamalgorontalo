<?php

namespace App\Events\Maintenance;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LaporanKerusakanMasuk
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $reportId,
        public readonly string $reporterName,
        public readonly string $reporterPhone,
        public readonly string $description,
        public readonly ?int $roomId = null,
        public readonly ?string $roomNumber = null,
    ) {}
}
