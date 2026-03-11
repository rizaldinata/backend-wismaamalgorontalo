<?php

namespace Modules\Finance\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;

class InvoiceRepository implements InvoiceRepositoryInterface
{
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
            ->where('updated_at', now()->month())
            ->where('updated_at', now()->year)
            ->sum('amount');
    }

    public function getTotalUnpaid(): float
    {
        return Invoice::where('status', InvoiceStatus::UNPAID->value)->sum('amount');
    }

    public function getMonthlyRevenue(int $months = 6): array
    {
        $revenueData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $total = Invoice::where('status', InvoiceStatus::PAID->value)
                ->whereMonth('updated_at', $date->month)
                ->whereYear('updated_at', $date->year)
                ->sum('amount');

            $revenueData[] = [
                'month' => $date->translatedFormat('M Y'),
                'total' => (float) $total
            ];
        }

        return $revenueData;
    }

    public function getDueInvoices(int $limit = 5): Collection
    {
        return Invoice::with(['lease.resident', 'lease.room'])
            ->where('status', InvoiceStatus::UNPAID->value)
            ->where('due_date', '<=', now()->addDay(7))
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get();
    }
}
