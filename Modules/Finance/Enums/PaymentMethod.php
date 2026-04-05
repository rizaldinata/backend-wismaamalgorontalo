<?php

namespace Modules\Finance\Enums;

enum PaymentMethod: string
{
    case MANUAL = 'manual';
    case MIDTRANS = 'midtrans';
}
