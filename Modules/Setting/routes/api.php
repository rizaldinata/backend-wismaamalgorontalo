<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\SettingController;

Route::prefix('v1')->group(function () {
    Route::get('settings/public', [SettingController::class, 'index']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->middleware('permission:setting-view');
            Route::post('/update-bulk', [SettingController::class, 'updateBulk'])->middleware('permission:setting-update');
        });
    });
});
