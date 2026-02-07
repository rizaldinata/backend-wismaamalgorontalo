<?php

use Modules\Auth\Models\Permission;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AdminPermissionController;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\PermissionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/permissions', [AuthController::class, 'myPermissions']);

    Route::prefix('admin')->group(function () {
        // route crud permission
        Route::get('/permissions', [AdminPermissionController::class, 'index'])->middleware('permission:view-permission');
        Route::post('/permissions', [AdminPermissionController::class, 'store'])->middleware('permission:create-permission');
        Route::put('/permissions/{id}', [AdminPermissionController::class, 'update'])->middleware('permission:update-permission');
        Route::delete('/permissions/{id}', [AdminPermissionController::class, 'destroy'])->middleware('permission:delete-permission');
    });
});
