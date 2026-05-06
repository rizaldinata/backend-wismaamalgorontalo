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
    // Resident routes
    Route::get('/my-reports', [DamageReportController::class, 'myReports'])
        ->middleware('permission:view-maintenance');
    Route::get('/{id}', [DamageReportController::class, 'show'])->where('id', '[0-9]+')
        ->middleware('permission:view-maintenance');
    Route::post('/', [DamageReportController::class, 'store'])
        ->middleware('permission:create-maintenance');

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminDamageReportController::class, 'index'])
            ->middleware('permission:view-damage-report');
        Route::get('/{id}', [AdminDamageReportController::class, 'show'])->where('id', '[0-9]+')
            ->middleware('permission:view-damage-report');
        Route::post('/{id}/updates', [AdminDamageReportController::class, 'storeUpdate'])
            ->middleware('permission:view-damage-report');
    });
});

// Maintenance Schedules (Jadwal Perawatan & Pembersihan)
Route::middleware(['auth:sanctum'])->prefix('v1/schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index'])
        ->middleware('permission:view-maintenance');
    Route::post('/', [ScheduleController::class, 'store'])
        ->middleware('permission:schedule-maintenance');
    Route::get('/{id}', [ScheduleController::class, 'show'])->where('id', '[0-9]+')
        ->middleware('permission:view-maintenance');
    Route::put('/{id}', [ScheduleController::class, 'update'])
        ->middleware('permission:schedule-maintenance');
    Route::delete('/{id}', [ScheduleController::class, 'destroy'])
        ->middleware('permission:schedule-maintenance');
    Route::post('/{id}/updates', [ScheduleController::class, 'storeUpdate'])
        ->middleware('permission:schedule-maintenance');
});
