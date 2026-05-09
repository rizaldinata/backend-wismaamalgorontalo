<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;
use Modules\Notification\Http\Controllers\NotificationLogController;


Route::prefix('notification/')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/send', [NotificationController::class, 'store']);

    Route::prefix('logs')->group(function () {
        Route::get('/', [NotificationLogController::class, 'index']);
        Route::post('/{id}/resend', [NotificationLogController::class, 'resend']);
    });
});
