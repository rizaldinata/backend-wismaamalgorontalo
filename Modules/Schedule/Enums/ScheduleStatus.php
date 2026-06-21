<?php

namespace Modules\Schedule\Enums;

enum ScheduleStatus: string
{
    case PENDING       = 'pending';
    case DP_TERBAYAR   = 'dp_terbayar';
    case TERKONFIRMASI = 'terkonfirmasi'; // Bayar lunas, start_date belum tiba
    case ACTIVE        = 'active';
    case FINISHED      = 'finished';
    case CANCELLED     = 'cancelled';
}
