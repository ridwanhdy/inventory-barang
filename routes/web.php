<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::prefix('admin')->group(function () {
    Route::get('/order/{order}/print-struk', [\App\Http\Controllers\OrderPrintController::class, 'printStruk'])->name('order.print-struk');
});