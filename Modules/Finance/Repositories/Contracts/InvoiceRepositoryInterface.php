<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Models\Invoice;

interface InvoiceRepositoryInterface
{
    public function findById(int $id): ?Invoice;
    public function updateStatus(Invoice $invoice, string $status): Invoice;
    public function create(array $data): Invoice;
    public function getTotalRevenueThisMonth(): float;
    public function getTotalUnpaid(): float;
    public function getMonthlyRevenue(int $months = 6): array;
    public function getDueInvoices(int $limit = 5): Collection;
}
