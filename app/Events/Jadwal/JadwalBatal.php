<?php

namespace App\Events\Jadwal;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JadwalBatal
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $scheduleId,
        public readonly int $roomId,
        public readonly string $roomNumber,
        public readonly ?string $tenantName = null,
        public readonly ?string $tenantPhone = null,
        public readonly ?string $tipeJadwal = null,
    ) {}
}
