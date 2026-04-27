<?php

namespace Modules\Finance\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::with(['lease.resident', 'lease.room'])->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
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

    public function getTotalRevenueThisMonth(): float
    {
        return Invoice::where('status', InvoiceStatus::PAID->value)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('amount');
    }

    public function getTotalUnpaid(): float
    {
        return Invoice::where('status', InvoiceStatus::UNPAID->value)->sum('amount');
    }

    public function getRevenueByRentalTypeThisMonth(string $rentalType): float
    {
        return Invoice::where('status', InvoiceStatus::PAID->value)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->whereHas('lease', function ($query) use ($rentalType) {
                $query->where('rental_type', $rentalType);
            })
            ->sum('amount');
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
            
            $monthlyRentTotal = (clone $monthlyQuery)->whereHas('lease', function ($q) {
                $q->where('rental_type', 'monthly');
            })->sum('amount');

            $dailyRentTotal = (clone $monthlyQuery)->whereHas('lease', function ($q) {
                $q->where('rental_type', 'daily');
            })->sum('amount');

            $revenueData[] = [
                'date_instance' => clone $date,
                'total' => (float) $total,
                'monthly_rent_revenue' => (float) $monthlyRentTotal,
                'daily_rent_revenue' => (float) $dailyRentTotal
            ];
        }

        return $revenueData;
    }

    public function getDueInvoices(int $limit = 5): Collection
    {
        return Invoice::with(['lease.resident', 'lease.room'])
            ->where('status', InvoiceStatus::UNPAID->value)
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get();
    }
}
