<?php

namespace Modules\Finance\Enums;

enum InvoiceStatus: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
