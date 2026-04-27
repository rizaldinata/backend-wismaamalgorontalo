<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Notification\Http\Requests\StoreNotificationRequest;
use Modules\Notification\Http\Resources\NotificationResource;
use Modules\Notification\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}


    public function store(StoreNotificationRequest $request): JsonResponse|NotificationResource
    {
        $data = $request->validated();
        
        $isSent = $this->notificationService->sendCustomNotification(
            $data['target_phone'], 
            $data['message_body']
        );

        if (!$isSent) {
            return response()->json(['error' => 'Failed to send notification'], 500);
        }

        return new NotificationResource([
            'target' => $data['target_phone'],
            'status' => 'sent'
        ]);
    }
}