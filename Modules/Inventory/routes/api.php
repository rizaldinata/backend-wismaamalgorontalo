<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;

Route::middleware(['auth:sanctum'])->prefix('inventory/')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])
        ->middleware('permission:view-inventory');

    Route::post('/', [InventoryController::class, 'store'])
        ->middleware('permission:create-inventory');

    Route::get('/{id}', [InventoryController::class, 'show'])
        ->middleware('permission:view-inventory');

    Route::put('/{id}', [InventoryController::class, 'update'])
        ->middleware('permission:update-inventory');

    Route::delete('/{id}', [InventoryController::class, 'destroy'])
        ->middleware('permission:delete-inventory');
});
