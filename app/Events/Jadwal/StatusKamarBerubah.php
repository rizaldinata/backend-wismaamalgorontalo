<?php

namespace App\Events\Jadwal;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StatusKamarBerubah
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $roomId,
        public readonly string $roomNumber,
        public readonly string $statusLama,
        public readonly string $statusBaru,
    ) {}
}
