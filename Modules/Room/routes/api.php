<?php

use Illuminate\Support\Facades\Route;
use Modules\Room\Http\Controllers\RoomController;

Route::get('rooms', [RoomController::class, 'index']);
Route::get('rooms/{id}', [RoomController::class, 'show']);
Route::get('rooms-schedules', [RoomController::class, 'schedules']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('rooms', [RoomController::class, 'store'])->middleware(['permission:create-room']);
    Route::put('rooms/{id}', [RoomController::class, 'update'])->middleware(['permission:update-room']);
    Route::delete('rooms/{id}', [RoomController::class, 'destroy'])->middleware(['permission:delete-room']);

    // routes untuk upload dan hapus foto
    Route::post('rooms/{id}/images', [RoomController::class, 'uploadImages'])->middleware(['permission:update-room']);
    Route::delete('rooms/{roomId}/images/{imageId}', [RoomController::class, 'deleteImage'])->middleware(['permission:update-room']);
});
