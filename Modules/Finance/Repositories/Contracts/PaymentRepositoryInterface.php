<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Models\Payment;

interface PaymentRepositoryInterface
{
    public function findOrFail(int $id): Payment;
    public function countPendingVerification(): int;
    public function getPendingPayments(int $limit = 5): Collection;
    public function findByReference(string $transactionId): ?Payment;
    public function create(array $data): Payment;
    public function update(Payment $payment, array $data): bool;
}
