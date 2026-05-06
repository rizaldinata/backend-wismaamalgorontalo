<?php

namespace Modules\Finance\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Payment;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Payment::with(['invoice.lease.resident', 'invoice.lease.room'])->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['resident_id'])) {
            $query->whereHas('invoice.lease', function ($q) use ($filters) {
                $q->where('resident_id', $filters['resident_id']);
            });
        }

        return $query->paginate($perPage);
    }
    public function findOrFail(int $id): Payment
    {
        return Payment::findOrFail($id);
    }

    public function countPendingVerification(): int
    {
        return Payment::where('status', PaymentStatus::PENDING->value)->count();
    }

    public function getPendingPayments(int $limit = 5): Collection
    {
        return Payment::with(['invoice.lease.resident', 'invoice.lease.room'])
            ->where('status', PaymentStatus::PENDING->value)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function findByReference(string $transactionId): ?Payment
    {
        return Payment::where('transaction_id', $transactionId)->first();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }
}
