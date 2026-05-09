<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\Invoice;

interface InvoiceRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function findById(int $id): ?Invoice;
    public function updateStatus(Invoice $invoice, string $status): Invoice;
    public function create(array $data): Invoice;
    public function getTotalRevenueThisMonth(?int $month = null, ?int $year = null): float;
    public function getTotalUnpaid(): float;
    public function getMonthlyRevenue(int $months = 6): array;
    public function getRevenueByRentalTypeThisMonth(string $rentalType, ?int $month = null, ?int $year = null): float;
    public function getTotalOverdueAmount(): float;
    public function countOverdueInvoices(): int;
    public function getDueInvoices(int $limit = 5): Collection;
}
