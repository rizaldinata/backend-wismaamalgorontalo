<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Notification\Http\Requests\StoreNotificationRequest;
use Modules\Notification\Services\NotificationService;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            if (! empty($data['user_id'])) {
                $isSent = $this->notificationService->sendToUser(
                    (int) $data['user_id'],
                    $data['message_body']
                );
            } else {
                $isSent = $this->notificationService->sendCustomNotification(
                    $data['target_phone'],
                    $data['message_body']
                );
            }
        } catch (\DomainException $e) {
            return $this->apiError($e->getMessage(), 422);
        }

        if (! $isSent) {
            return $this->apiError('Gagal mengirim notifikasi. Periksa koneksi provider.', 500);
        }

        return $this->apiSuccess(null, 'Notifikasi berhasil dikirim.', 201);
    }

    public function recipients(): JsonResponse
    {
        $recipients = $this->notificationService->getRecipients();

        return $this->apiSuccess($recipients, 'Daftar penerima berhasil dimuat.');
    }
}
