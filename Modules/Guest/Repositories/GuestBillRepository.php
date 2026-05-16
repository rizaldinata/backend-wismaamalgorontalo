<?php

namespace Modules\Guest\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Guest\Models\GuestBill;
use Modules\Guest\Repositories\Contracts\GuestBillRepositoryInterface;

class GuestBillRepository implements GuestBillRepositoryInterface
{
    public function findByGuestId(int $guestId): ?GuestBill
    {
        return GuestBill::where('guest_id', $guestId)->first();
    }

    public function findById(int $id): ?GuestBill
    {
        return GuestBill::find($id);
    }

    public function findByTransactionId(string $txId): ?GuestBill
    {
        return GuestBill::where('transaction_id', $txId)->first();
    }

    public function create(array $data): GuestBill
    {
        return GuestBill::create($data);
    }

    public function update(GuestBill $bill, array $data): GuestBill
    {
        $bill->update($data);
        $bill->refresh();

        return $bill;
    }

    public function getAllPaginated(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = $filters['search'] ?? null;

        $query = GuestBill::with([
            'guest.lease.resident.user',
            'guest.lease.room',
        ])->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', function ($gq) use ($search) {
                    $gq->where('name', 'like', "%{$search}%");
                })->orWhereHas('guest.lease.resident.user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                });
            });
        }

        return $query->paginate($perPage);
    }
}
