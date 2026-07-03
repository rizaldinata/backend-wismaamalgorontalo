<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Services\NotificationService;

class NotificationLogController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = array_filter([
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ]);

        $logs = $this->notificationService->getLogHistory($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'unread_count' => $this->notificationService->countUnread(),
        ]);
    }

    public function markAllAsRead()
    {
        $updated = $this->notificationService->markAllAsRead();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function resend(int $id): JsonResponse
    {
        try {
            $isSent = $this->notificationService->resendFailedNotification($id);

            if ($isSent) {
                return $this->apiSuccess(null, 'Notifikasi berhasil dikirim ulang.');
            }

            return $this->apiError('Gagal mengirim ulang notifikasi. Periksa koneksi provider.', 500);

        } catch (\DomainException $e) {
            return $this->apiError($e->getMessage(), 403);
        }
    }

    public function summary(): JsonResponse
    {
        $summary = $this->notificationService->getSummary();

        return $this->apiSuccess($summary, 'Ringkasan notifikasi berhasil dimuat.');
    }
}
