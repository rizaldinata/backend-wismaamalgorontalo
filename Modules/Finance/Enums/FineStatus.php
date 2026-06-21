<?php

namespace Modules\Finance\Enums;

enum FineStatus: string
{
    case UNPAID    = 'unpaid';
    case PAID      = 'paid';
    case WAIVED    = 'waived';
    case CANCELLED = 'cancelled';
}
