<?php

use Illuminate\Support\Facades\Route;
use Modules\Room\Http\Controllers\RoomController;

Route::get('rooms', [RoomController::class, 'index']);
Route::get('rooms/{id}', [RoomController::class, 'show']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('rooms', [RoomController::class, 'store']);
    Route::put('rooms/{id}', [RoomController::class, 'update']);
    Route::delete('rooms/{id}', [RoomController::class, 'destroy']);

    // routes untuk upload dan hapus foto
    Route::post('rooms/{id}/images', [RoomController::class, 'uploadImages']);
    Route::delete('rooms/{roomId}/images/{imageId}', [RoomController::class, 'deleteImage']);
});
