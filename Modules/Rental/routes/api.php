<?php

use Illuminate\Support\Facades\Route;
use Modules\Rental\Http\Controllers\RentalController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('rentals', RentalController::class)->names('rental');
});
