<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\BankAccountController;
use Modules\Setting\Http\Controllers\MidtransFeeController;
use Modules\Setting\Http\Controllers\PaymentMethodSettingController;
use Modules\Setting\Http\Controllers\SettingController;

Route::prefix('v1')->group(function () {
    Route::get('settings/public', [SettingController::class, 'index']);
    Route::get('settings/bank-accounts/public', [BankAccountController::class, 'publicIndex']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->middleware('permission:setting-view');
            Route::post('/update-bulk', [SettingController::class, 'updateBulk'])->middleware('permission:setting-update');

            Route::prefix('payment-methods')->middleware('permission:setting-update')->group(function () {
                Route::get('/', [PaymentMethodSettingController::class, 'index']);
                Route::put('/', [PaymentMethodSettingController::class, 'update']);
            });

            Route::prefix('feature-toggles')->group(function () {
                Route::get('/', [\Modules\Setting\Http\Controllers\FeatureToggleController::class, 'index']);
                Route::patch('/{key}', [\Modules\Setting\Http\Controllers\FeatureToggleController::class, 'update'])->middleware('role:super-admin');
            });

            Route::prefix('midtrans-fees')->middleware('permission:setting-update')->group(function () {
                Route::get('/', [MidtransFeeController::class, 'index'])->withoutMiddleware('permission:setting-update')->middleware('permission:setting-view');
                Route::put('/', [MidtransFeeController::class, 'update']);
            });

            Route::prefix('bank-accounts')->group(function () {
                Route::get('/', [BankAccountController::class, 'index'])->middleware('permission:setting-view');
                Route::post('/', [BankAccountController::class, 'store'])->middleware('permission:setting-update');
                Route::put('/{id}', [BankAccountController::class, 'update'])->middleware('permission:setting-update');
                Route::delete('/{id}', [BankAccountController::class, 'destroy'])->middleware('permission:setting-update');
            });
        });
    });
});
