<?php

namespace Modules\Finance\Contracts;

use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;

interface PaymentStrategyInterface
{
    public function process(Invoice $invoice, array $data): Payment;
}
