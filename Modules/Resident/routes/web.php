<?php

use Illuminate\Support\Facades\Route;
use Modules\Resident\Http\Controllers\ResidentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('residents', ResidentController::class)->names('resident');
});
