<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Http\Controllers\MaintenanceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('maintenances', MaintenanceController::class)->names('maintenance');
});
