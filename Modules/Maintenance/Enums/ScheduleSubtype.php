<?php

namespace Modules\Maintenance\Enums;

enum ScheduleSubtype: string
{
    case RUTIN        = 'rutin';
    case DEEP_CLEANING = 'deep_cleaning';
    case DARURAT      = 'darurat';
    case PERBAIKAN    = 'perbaikan';
    case MAINTENANCE  = 'maintenance';
}
