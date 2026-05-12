<?php

use Illuminate\Support\Facades\Route;
use Modules\Resident\Http\Controllers\AdminUserController;
use Modules\Resident\Http\Controllers\ResidentController;
use Modules\Resident\Http\Controllers\AdminResidentController;

Route::middleware(['auth:sanctum'])->prefix('resident')->group(function () {
    // Route profile
    Route::get('profile', [ResidentController::class, 'show'])->middleware('permission:access-resident-area|complete-resident-profile');
    Route::post('profile', [ResidentController::class, 'store'])->middleware('permission:complete-resident-profile');
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/residents', [AdminResidentController::class, 'index'])->middleware('permission:view-resident');
    Route::get('/residents/{id}', [AdminResidentController::class, 'show'])->middleware('permission:view-resident');
});
