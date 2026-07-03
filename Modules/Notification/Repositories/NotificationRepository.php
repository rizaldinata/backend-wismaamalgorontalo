<?php

namespace Modules\Notification\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Models\NotificationLog;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function logNotification(NotificationType $type, string $target, string $message, string $status, ?string $error = null): NotificationLog
    {
        return NotificationLog::create([
            'type' => $type->value,
            'target_phone' => $target,
            'message_body' => $message,
            'status' => $status,
            'error_response' => $error,
        ]);
    }

    public function getLogsPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = NotificationLog::latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['search'])) {
            $query->where('target_phone', 'LIKE', '%'.$filters['search'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?NotificationLog
    {
        return NotificationLog::findOrFail($id);
    }

    public function updateStatus(NotificationLog $log, string $status, ?string $error = null): bool
    {
        return $log->update([
            'status' => $status,
            'error_response' => $error,
        ]);
    }

    public function getSummary(): array
    {
        $total = NotificationLog::count();
        $sent = NotificationLog::where('status', 'sent')->count();
        $failed = NotificationLog::where('status', 'failed')->count();
        $today = NotificationLog::whereDate('created_at', now()->toDateString())->count();

        $byType = NotificationLog::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'today' => $today,
            'by_type' => $byType,
        ];
    }

    public function getRecipients(): Collection
    {
        return DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', 'Modules\\Auth\\Models\\User')
            ->whereIn('roles.name', ['member', 'resident'])
            ->select('users.id', 'users.name', 'user_profiles.phone_number')
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    public function markAllAsRead(): int
    {
        return NotificationLog::where('is_read', false)->update(['is_read' => true]);
    }

    public function countUnread(): int
    {
        return NotificationLog::where('is_read', false)->count();
    }
}
