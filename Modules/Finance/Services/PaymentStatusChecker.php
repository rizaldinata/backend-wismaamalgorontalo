<?php

namespace Modules\Finance\Services;

use App\Contracts\PaymentStatusCheckerInterface;
use Illuminate\Support\Facades\DB;

class PaymentStatusChecker implements PaymentStatusCheckerInterface
{
    public function hasActivePaymentForSchedule(int $scheduleId): bool
    {
        return DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('invoices.schedule_id', $scheduleId)
            ->whereIn('payments.status', ['pending', 'verified', 'paid'])
            ->exists();
    }

    public function hasPendingExtensionInvoice(int $scheduleId, string $endDate): bool
    {
        return DB::table('invoices')
            ->where('schedule_id', $scheduleId)
            ->where('period_start', '>', $endDate)
            ->where('payment_expires_at', '>', now())
            ->exists();
    }
}
