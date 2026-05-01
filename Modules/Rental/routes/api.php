<?php

use Illuminate\Support\Facades\Route;
use Modules\Rental\Http\Controllers\RentalController;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get(
        'rentals',
        [RentalController::class, 'index']
    )->middleware('permission:view-lease');

    Route::post(
        'rentals',
        [RentalController::class, 'store']
    )->middleware('permission:create-lease');

});