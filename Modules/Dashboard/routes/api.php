<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;

Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
    Route::get('/admin', [DashboardController::class, 'getAdminStats'])->middleware('permission:view-dashboard');
    Route::get('/resident', [DashboardController::class, 'getResidentStats'])->middleware('permission:view-resident-dashboard');
});
