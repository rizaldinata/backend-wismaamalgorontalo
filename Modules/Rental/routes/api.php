<?php

use Illuminate\Support\Facades\Route;
use Modules\Rental\Http\Controllers\RentalController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('rentals', RentalController::class)->names('rental');
    Route::post('rentals/{id}/extend', [RentalController::class, 'extend']);
});
