<?php

namespace Modules\Guest\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Guest\Models\GuestBill;
use Modules\Guest\Repositories\Contracts\GuestBillRepositoryInterface;

class GuestBillRepository implements GuestBillRepositoryInterface
{
    public function findByGuestId(int $guestId): ?GuestBill
    {
        return GuestBill::where('guest_id', $guestId)->latest()->first();
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
        $status = $filters['status'] ?? null;

        $query = GuestBill::with(['guest'])->orderByDesc('created_at');

        if ($search) {
            $query->whereHas('guest', function ($gq) use ($search) {
                $gq->where('name', 'like', "%{$search}%")
                    ->orWhere('tenant_name', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }
}
