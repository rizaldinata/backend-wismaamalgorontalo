<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Http\Controllers\AdminDamageReportController;
use Modules\Maintenance\Http\Controllers\DamageReportController;
use Modules\Maintenance\Http\Controllers\MediaController;
use Modules\Maintenance\Http\Controllers\ScheduleController;

// Public: media proxy (CORS fix for Flutter Web)
Route::get('maintenance/media/{path}', [MediaController::class, 'show'])->where('path', '.*');

// Damage Reports (Laporan Kerusakan dari Penghuni)
Route::middleware(['auth:sanctum'])->prefix('v1/damage-reports')->group(function () {
    // Resident routes (Only for active residents)
    Route::middleware(['can:resident-access'])->group(function () {
        Route::get('/my-reports', [DamageReportController::class, 'myReports']);
        Route::get('/{id}', [DamageReportController::class, 'show'])->where('id', '[0-9]+');
        Route::post('/', [DamageReportController::class, 'store']);
    });

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminDamageReportController::class, 'index']);
        Route::get('/{id}', [AdminDamageReportController::class, 'show'])->where('id', '[0-9]+');
        Route::post('/{id}/updates', [AdminDamageReportController::class, 'storeUpdate']);
    });
});

// Maintenance Schedules (Jadwal Perawatan & Pembersihan)
Route::middleware(['auth:sanctum'])->prefix('v1/schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/{id}', [ScheduleController::class, 'show'])->where('id', '[0-9]+');
    Route::put('/{id}', [ScheduleController::class, 'update']);
    Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    Route::post('/{id}/updates', [ScheduleController::class, 'storeUpdate']);
});
