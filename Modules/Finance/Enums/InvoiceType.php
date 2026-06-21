<?php

namespace Modules\Finance\Enums;

enum InvoiceType: string
{
    case SEWA      = 'sewa';
    case EXTENSION = 'extension';
    case FINE      = 'fine';
    case DP        = 'dp';
    case PELUNASAN = 'pelunasan';
}
