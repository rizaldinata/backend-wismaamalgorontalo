<?php

namespace Modules\Finance\Repositories\Contracts;

use Modules\Finance\Models\Invoice;

interface InvoiceRepositoryInterface
{
    public function findById(int $id): ?Invoice;
    public function updateStatus(Invoice $invoice, string $status): Invoice;
    public function create(array $data): Invoice;
}
