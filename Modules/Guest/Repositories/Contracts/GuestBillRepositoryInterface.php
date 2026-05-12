<?php

namespace Modules\Guest\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Guest\Models\GuestBill;

interface GuestBillRepositoryInterface
{
    public function findByGuestId(int $guestId): ?GuestBill;
    public function findById(int $id): ?GuestBill;
    public function findByTransactionId(string $txId): ?GuestBill;
    public function create(array $data): GuestBill;
    public function update(GuestBill $bill, array $data): GuestBill;
    public function getAllPaginated(array $filters): LengthAwarePaginator;
}
