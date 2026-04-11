<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Http\Controllers\AdminMaintenanceController;
use Modules\Maintenance\Http\Controllers\MaintenanceController;
use Modules\Maintenance\Http\Controllers\MediaController;

Route::get('maintenance/media/{path}', [MediaController::class, 'show'])->where('path', '.*');

Route::middleware(['auth:sanctum'])->prefix('v1/maintenance')->group(function () {
    // Resident routes
    Route::get('/my-requests', [MaintenanceController::class, 'myReports']);
    Route::get('/requests/{id}', [MaintenanceController::class, 'show']);
    Route::post('/requests', [MaintenanceController::class, 'store']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/requests', [AdminMaintenanceController::class, 'index']);
        Route::get('/requests/{id}', [AdminMaintenanceController::class, 'show']);
        Route::post('/requests/{id}/updates', [AdminMaintenanceController::class, 'storeUpdate']);
    });
});
