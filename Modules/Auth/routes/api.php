<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\PermissionController;
use Modules\Auth\Http\Controllers\RoleController;
use Modules\Auth\Http\Controllers\UserController;
use Modules\Auth\Http\Controllers\AuthController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/permissions', [AuthController::class, 'myPermissions']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    Route::prefix('admin')->group(function () {
        // route crud permission
        Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:view-permission');
        Route::post('/permissions', [PermissionController::class, 'store'])->middleware('permission:create-permission');
        Route::get('/permissions/{id}', [PermissionController::class, 'show'])->middleware('permission:view-permission');
        Route::put('/permissions/{id}', [PermissionController::class, 'update'])->middleware('permission:update-permission');
        Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->middleware('permission:delete-permission');

        // route crud role
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:view-role');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:create-role');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('permission:view-role');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:update-role');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete-role');

        // route crud user
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:view-user');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:create-user');
        Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:view-user');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:update-user');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete-user');
    });
});
