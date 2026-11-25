<?php

use Illuminate\Support\Facades\Route;
use Ldonjibson\ApiExplorer\Http\Controllers\ApiExplorerController;
use Ldonjibson\ApiExplorer\Http\Controllers\ApiProxyController;
use Ldonjibson\ApiExplorer\Http\Middleware\EnsureExplorerEnabled;

Route::middleware(['web', EnsureExplorerEnabled::class])->group(function () {
    Route::get('/', [ApiExplorerController::class, 'index'])->name('index');
    Route::get('endpoints', [ApiExplorerController::class, 'endpoints'])->name('endpoints');
    Route::post('import', [ApiExplorerController::class, 'import'])->name('import');
    Route::post('send', [ApiProxyController::class, 'send'])->name('send');
});
