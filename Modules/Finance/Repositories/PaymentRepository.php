<?php

namespace Modules\Finance\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Payment;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Payment::with(['invoice.schedule.room'])->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['schedule_ids'])) {
            $query->whereHas('invoice', function ($q) use ($filters) {
                $q->whereIn('schedule_id', $filters['schedule_ids']);
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
        return Payment::with(['invoice.schedule.room'])
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
