<?php

namespace App\Events\Jadwal;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JadwalDibuat
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $scheduleId,
        public readonly int $roomId,
        public readonly string $roomNumber,
        public readonly string $tipeJadwal,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $tenantName = null,
        public readonly ?string $tenantPhone = null,
        public readonly ?float $agreedPrice = null,
        public readonly string $source = 'lease', // 'lease' | 'schedule'
        public readonly ?int $tenantUserId = null,
    ) {}
}
