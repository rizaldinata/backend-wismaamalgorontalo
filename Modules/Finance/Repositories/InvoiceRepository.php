<?php

namespace Modules\Finance\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::with(['payments'])->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['schedule_ids'])) {
            $query->whereIn('schedule_id', $filters['schedule_ids']);
        }

        if (! empty($filters['tenant_user_id'])) {
            $query->where('tenant_user_id', $filters['tenant_user_id']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Invoice
    {
        return Invoice::find($id);
    }

    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);

        return $invoice;
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function getTotalRevenueThisMonth(?int $month = null, ?int $year = null): float
    {
        $year = $year ?? now()->year;

        $query = Invoice::where('invoices.status', InvoiceStatus::PAID->value)
            ->whereYear('invoices.updated_at', $year)
            ->selectRaw(
                'COALESCE(SUM(invoices.amount - COALESCE(
                    (SELECT p.midtrans_fee FROM payments p
                     WHERE p.invoice_id = invoices.id
                       AND p.fee_bearer = ?
                       AND p.status = ?
                     LIMIT 1), 0)), 0) as net_revenue',
                ['merchant', PaymentStatus::PAID->value]
            );

        if ($month !== null) {
            $query->whereMonth('invoices.updated_at', $month);
        }

        return (float) ($query->value('net_revenue') ?? 0);
    }

    public function getTotalUnpaid(): float
    {
        return Invoice::where('status', InvoiceStatus::UNPAID->value)->sum('amount');
    }

    public function getRevenueByRentalTypeThisMonth(string $rentalType, ?int $month = null, ?int $year = null): float
    {
        // rental_type concept removed after Rental module deprecation
        // 'daily' always returns 0; 'monthly' returns total paid revenue
        if ($rentalType === 'daily') {
            return 0.0;
        }

        $year = $year ?? now()->year;
        $query = Invoice::where('invoices.status', InvoiceStatus::PAID->value)
            ->whereYear('invoices.updated_at', $year)
            ->selectRaw(
                'COALESCE(SUM(invoices.amount - COALESCE(
                    (SELECT p.midtrans_fee FROM payments p
                     WHERE p.invoice_id = invoices.id
                       AND p.fee_bearer = ?
                       AND p.status = ?
                     LIMIT 1), 0)), 0) as net_revenue',
                ['merchant', PaymentStatus::PAID->value]
            );

        if ($month !== null) {
            $query->whereMonth('invoices.updated_at', $month);
        }

        return (float) ($query->value('net_revenue') ?? 0);
    }

    public function getTotalOverdueAmount(): float
    {
        return Invoice::where('status', InvoiceStatus::UNPAID->value)
            ->where('due_date', '<', now()->startOfDay())
            ->sum('amount');
    }

    public function countOverdueInvoices(): int
    {
        return Invoice::where('status', InvoiceStatus::UNPAID->value)
            ->where('due_date', '<', now()->startOfDay())
            ->count();
    }

    public function getMonthlyRevenue(int $months = 6): array
    {
        $revenueData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $total = (float) Invoice::where('invoices.status', InvoiceStatus::PAID->value)
                ->whereMonth('invoices.updated_at', $date->month)
                ->whereYear('invoices.updated_at', $date->year)
                ->selectRaw(
                    'COALESCE(SUM(invoices.amount - COALESCE(
                        (SELECT p.midtrans_fee FROM payments p
                         WHERE p.invoice_id = invoices.id
                           AND p.fee_bearer = ?
                           AND p.status = ?
                         LIMIT 1), 0)), 0) as net_revenue',
                    ['merchant', PaymentStatus::PAID->value]
                )
                ->value('net_revenue') ?? 0;

            $revenueData[] = [
                'date_instance'        => clone $date,
                'total'                => $total,
                'monthly_rent_revenue' => $total,
                'daily_rent_revenue'   => 0.0,
            ];
        }

        return $revenueData;
    }

    public function getDueInvoices(int $limit = 5): Collection
    {
        return Invoice::where('status', InvoiceStatus::UNPAID->value)
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get();
    }
}
