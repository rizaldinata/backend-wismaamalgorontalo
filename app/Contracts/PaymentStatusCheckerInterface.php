<?php

namespace App\Contracts;

interface PaymentStatusCheckerInterface
{
    public function hasActivePaymentForSchedule(int $scheduleId): bool;

    public function hasPendingExtensionInvoice(int $scheduleId, string $endDate): bool;
}
