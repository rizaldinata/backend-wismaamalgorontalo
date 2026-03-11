<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    public function countPendingVerification(): int;
    public function getPendingPayments(int $limit = 5): Collection;
}
