<?php

namespace Modules\Resident\Enums;

enum LeaseStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case FINISHED = 'finished';
}
