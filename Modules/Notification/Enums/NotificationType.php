<?php

namespace Modules\Notification\Enums;

enum NotificationType: string
{
    case PAYMENT_RECEIPT = 'payment_receipt';
    case PAYMENT_REMINDER = 'payment_reminder';
    case MANUAL_BROADCAST = 'manual_broadcast';
    case SYSTEM_ALERT = 'system_alert';
}