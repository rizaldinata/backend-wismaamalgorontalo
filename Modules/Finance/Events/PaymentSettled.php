<?php

namespace Modules\Finance\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSettled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;

    public function __construct($payment)
    {
        $this->payment = $payment;
    }
}
