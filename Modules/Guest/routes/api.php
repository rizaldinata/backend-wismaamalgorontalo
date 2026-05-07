<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Http\Controllers\AdminGuestController;
use Modules\Guest\Http\Controllers\GuestController;

Route::middleware(['auth:sanctum'])->prefix('guests')->group(function () {
    Route::get('/', [GuestController::class, 'index'])->middleware('permission:view-my-guest');
    Route::post('/', [GuestController::class, 'store'])->middleware('permission:create-guest');
    Route::delete('/{id}', [GuestController::class, 'destroy'])->middleware('permission:delete-guest');
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/guests', [AdminGuestController::class, 'index'])->middleware('permission:view-guest');
});
