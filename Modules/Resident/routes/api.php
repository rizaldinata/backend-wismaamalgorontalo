<?php

use Illuminate\Support\Facades\Route;
use Modules\Resident\Http\Controllers\AdminLeaseController;
use Modules\Resident\Http\Controllers\AdminUserController;
use Modules\Resident\Http\Controllers\LeaseController;
use Modules\Resident\Http\Controllers\ResidentController;

Route::middleware(['auth:sanctum'])->prefix('resident')->group(function () {
    // Route profile
    Route::get('profile', [ResidentController::class, 'show']);
    Route::post('profile', [ResidentController::class, 'store']);

    // Route lease
    Route::post('leases', [LeaseController::class, 'store']);
    Route::get('leases', [LeaseController::class, 'myLeases']);
    Route::post('leases/{id}/pay', [LeaseController::class, 'uploadPayment']);
});

// Route admin
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('leases', [AdminLeaseController::class, 'index']);
    Route::get('leases/{id}', [AdminLeaseController::class, 'show']);
    Route::post('leases/{id}/status', [AdminLeaseController::class, 'updateStatus']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('roles-options', [AdminUserController::class, 'getRoles']);
    Route::apiResource('users', AdminUserController::class);
});
