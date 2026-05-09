<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\InvoiceController;

Route::get('/finance/invoices/{id}/print', [InvoiceController::class, 'printPdf'])
    ->name('finance.invoice.print')
    ->middleware('signed');
