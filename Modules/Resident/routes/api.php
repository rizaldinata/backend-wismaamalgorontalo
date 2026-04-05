<?php

use Illuminate\Support\Facades\Route;
use Modules\Resident\Http\Controllers\AdminUserController;
use Modules\Resident\Http\Controllers\ResidentController;

Route::middleware(['auth:sanctum'])->prefix('resident')->group(function () {
    // Route profile
    Route::get('profile', [ResidentController::class, 'show']);
    Route::post('profile', [ResidentController::class, 'store']);
});
