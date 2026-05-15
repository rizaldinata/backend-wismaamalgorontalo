<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Http\Controllers\ScheduleController;

Route::middleware(['auth:sanctum'])->prefix('v1/room-schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/kamar/{roomId}', [ScheduleController::class, 'byKamar']);
    Route::post('/{id}/aktifkan', [ScheduleController::class, 'aktifkan']);
    Route::post('/{id}/selesaikan', [ScheduleController::class, 'selesaikan']);
    Route::post('/{id}/batalkan', [ScheduleController::class, 'batalkan']);
});
