<?php

namespace Modules\Rental\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Rental\Models\Lease;

class LeaseEnded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Lease $lease) {}
}
