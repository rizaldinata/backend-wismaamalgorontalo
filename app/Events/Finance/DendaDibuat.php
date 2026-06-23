<?php

namespace App\Events\Finance;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DendaDibuat
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $fineId,
        public readonly string $tenantName,
        public readonly string $tenantPhone,
        public readonly float $amount,
        public readonly string $reason,
    ) {}
}
