<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Http\Controllers\AdminGuestBillController;
use Modules\Guest\Http\Controllers\AdminGuestController;
use Modules\Guest\Http\Controllers\GuestBillController;
use Modules\Guest\Http\Controllers\GuestController;

Route::middleware(['auth:sanctum'])->prefix('guests')->group(function () {
    Route::get('/', [GuestController::class, 'index'])->middleware('permission:view-my-guest');
    Route::post('/', [GuestController::class, 'store'])->middleware('permission:create-guest');
    Route::delete('/{id}', [GuestController::class, 'destroy'])->middleware('permission:delete-guest');

    // Resident bill routes
    Route::get('/{guestId}/bill', [GuestBillController::class, 'show'])->middleware('permission:pay-guest-bill');
    Route::post('/{guestId}/bill/pay', [GuestBillController::class, 'pay'])->middleware('permission:pay-guest-bill');
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/guests', [AdminGuestController::class, 'index'])->middleware('permission:view-guest');
    Route::post('/guests', [AdminGuestController::class, 'store'])->middleware('permission:create-guest');

    // Admin bill routes
    Route::get('/guest-bills', [AdminGuestBillController::class, 'index'])->middleware('permission:view-guest-bill');
    Route::post('/guest-bills/{id}/verify', [AdminGuestBillController::class, 'verify'])->middleware('permission:verify-guest-bill');
});

// Midtrans webhook (no auth)
Route::post('/guests/bills/midtrans/notification', [GuestBillController::class, 'midtransNotification']);
