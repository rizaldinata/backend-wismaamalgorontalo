<?php

use Illuminate\Support\Facades\Route;
use Modules\Iventory\Http\Controllers\IventoryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('iventories', IventoryController::class)->names('iventory');
});
