<?php

namespace Modules\Finance\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::with(['schedule.room'])->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['schedule_ids'])) {
            $query->whereIn('schedule_id', $filters['schedule_ids']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Invoice
    {
        return Invoice::findOrFail($id);
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

        $query = Invoice::where('status', InvoiceStatus::PAID->value)
            ->whereYear('updated_at', $year);

        if ($month !== null) {
            $query->whereMonth('updated_at', $month);
        }

        return $query->sum('amount');
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
        $query = Invoice::where('status', InvoiceStatus::PAID->value)->whereYear('updated_at', $year);

        if ($month !== null) {
            $query->whereMonth('updated_at', $month);
        }

        return (float) $query->sum('amount');
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

            $monthlyQuery = Invoice::where('status', InvoiceStatus::PAID->value)
                ->whereMonth('updated_at', $date->month)
                ->whereYear('updated_at', $date->year);

            $total = (clone $monthlyQuery)->sum('amount');

            $revenueData[] = [
                'date_instance' => clone $date,
                'total' => (float) $total,
                'monthly_rent_revenue' => (float) $total,
                'daily_rent_revenue' => 0.0,
            ];
        }

        return $revenueData;
    }

    public function getDueInvoices(int $limit = 5): Collection
    {
        return Invoice::with(['schedule.room'])
            ->where('status', InvoiceStatus::UNPAID->value)
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get();
    }
}
