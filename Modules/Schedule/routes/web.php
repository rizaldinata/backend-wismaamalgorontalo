<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Http\Controllers\ScheduleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('schedules', ScheduleController::class)->names('schedule');
});
