<?php

use Illuminate\Support\Facades\Route;
use Modules\Iventory\Http\Controllers\IventoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('iventories', IventoryController::class)->names('iventory');
});
