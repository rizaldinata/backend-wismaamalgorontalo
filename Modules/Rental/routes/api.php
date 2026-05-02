<?php

use Illuminate\Support\Facades\Route;
use Modules\Rental\Http\Controllers\RentalController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Admin Routes
    Route::get('rentals', [RentalController::class, 'index'])->middleware('permission:view-lease');
    Route::patch('rentals/{id}/status', [RentalController::class, 'updateStatus'])->middleware('permission:approve-lease');

    // Resident Routes
    Route::get('rentals/my', [RentalController::class, 'myLeases'])->middleware('permission:view-lease');
    Route::post('rentals', [RentalController::class, 'store'])->middleware('permission:create-lease');
    Route::post('rentals/{id}/extend', [RentalController::class, 'extend'])->middleware('permission:create-lease');
});
