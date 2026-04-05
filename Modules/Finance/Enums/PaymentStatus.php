<?php

namespace Modules\Finance\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case FAILED = 'failed';
    case PAID = 'paid';
}
