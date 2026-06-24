<?php

namespace Modules\Finance\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\RefundRequest;
use Modules\Finance\Repositories\Contracts\RefundRequestRepositoryInterface;

class RefundRequestRepository implements RefundRequestRepositoryInterface
{
    public function create(array $data): RefundRequest
    {
        return RefundRequest::create($data);
    }

    public function findOrFail(int $id): RefundRequest
    {
        return RefundRequest::with(['payment.invoice', 'schedule.room'])->findOrFail($id);
    }

    public function update(RefundRequest $refundRequest, array $data): RefundRequest
    {
        $refundRequest->update($data);

        return $refundRequest->fresh();
    }

    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = RefundRequest::with(['payment.invoice', 'schedule.room'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['schedule_ids'])) {
            $query->whereIn('schedule_id', $filters['schedule_ids']);
        }

        return $query->paginate($perPage);
    }

    public function hasPendingBySchedule(int $scheduleId): bool
    {
        return RefundRequest::where('schedule_id', $scheduleId)
            ->where('status', 'pending')
            ->exists();
    }
}
