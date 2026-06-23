<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Http\Controllers\ScheduleController;
use Modules\Schedule\Http\Controllers\AdminResidentController;

// Rute lainnya yang membutuhkan otentikasi
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/admin/residents', [AdminResidentController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->prefix('v1/room-schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/my', [ScheduleController::class, 'mySchedules']);
    Route::get('/kamar/{roomId}', [ScheduleController::class, 'byKamar']);
    Route::get('/{id}', [ScheduleController::class, 'show']);
    Route::post('/{id}/aktifkan', [ScheduleController::class, 'aktifkan']);
    Route::post('/{id}/selesaikan', [ScheduleController::class, 'selesaikan']);
    Route::post('/{id}/batalkan', [ScheduleController::class, 'batalkan']);
});
