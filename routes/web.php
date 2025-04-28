<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::prefix('stock')->group(function () {
    Route::get('/', [StockController::class, 'index'])->name('stock.index');
    Route::get('/scan', [StockController::class, 'scan'])->name('stock.scan');
    Route::post('/process-scan', [StockController::class, 'processScan'])->name('stock.process-scan');
    Route::get('/product/{barcode}', [StockController::class, 'getProductByBarcode'])->name('stock.get-product');
});
