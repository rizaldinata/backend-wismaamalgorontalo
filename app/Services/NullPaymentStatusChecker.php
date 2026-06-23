<?php

namespace App\Services;

use App\Contracts\PaymentStatusCheckerInterface;

// Default null implementation — active when Finance module is OFF.
// Returns false so Schedule commands proceed normally without Finance data.
class NullPaymentStatusChecker implements PaymentStatusCheckerInterface
{
    public function hasActivePaymentForSchedule(int $scheduleId): bool
    {
        return false;
    }

    public function hasPendingExtensionInvoice(int $scheduleId, string $endDate): bool
    {
        return false;
    }
}
