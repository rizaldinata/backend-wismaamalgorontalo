<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;

Route::middleware(['auth:sanctum'])->prefix('inventory/')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])
        ->middleware('permission:inventory-view');

    Route::post('/', [InventoryController::class, 'store'])
        ->middleware('permission:inventory-create');

    Route::get('/{id}', [InventoryController::class, 'show'])
        ->middleware('permission:inventory-view');

    Route::put('/{id}', [InventoryController::class, 'update'])
        ->middleware('permission:inventory-update');

    Route::delete('/{id}', [InventoryController::class, 'destroy'])
        ->middleware('permission:inventory-delete');
});
