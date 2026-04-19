<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Notification\Services\NotificationService;

class NotificationLogController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $logs = $this->notificationService->getLogHistory($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function resend(int $id)
    {
        try {
            $isSent = $this->notificationService->resendFailedNotification($id);

            if ($isSent) {
                return response()->json(['message' => 'Notifikasi berhasil dikirim ulang.']);
            }

            return response()->json(['message' => 'Gagal mengirim ulang notifikasi. Periksa koneksi provider.'], 500);
            
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}