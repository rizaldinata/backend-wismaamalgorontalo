<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;
use Modules\Notification\Http\Controllers\NotificationLogController;

Route::prefix('notification/')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/send', [NotificationController::class, 'store'])
        ->middleware('permission:notification-send');
    Route::get('/recipients', [NotificationController::class, 'recipients'])
        ->middleware('permission:notification-send');

    Route::prefix('logs')->middleware('permission:notification-log-view')->group(function () {
        Route::get('/', [NotificationLogController::class, 'index']);
        Route::get('/summary', [NotificationLogController::class, 'summary']);
        Route::post('/{id}/resend', [NotificationLogController::class, 'resend'])
            ->withoutMiddleware('permission:notification-log-view')
            ->middleware('permission:notification-send');
    });
});
