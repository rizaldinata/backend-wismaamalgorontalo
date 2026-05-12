<?php

use Illuminate\Support\Facades\Route;
use Modules\Rental\Http\Controllers\RentalController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('rentals', RentalController::class)->names('rental');
});
