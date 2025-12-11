<?php

use Illuminate\Support\Facades\Route;
use Modules\Resident\Http\Controllers\ResidentController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('residents', ResidentController::class)->names('resident');
});
