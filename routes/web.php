<?php

use Illuminate\Support\Facades\Route;
use TeamNiftyGmbH\DataTable\Controllers\AssetController;

Route::name('tall-datatables.')->prefix('/tall-datatables')->group(function (): void {
    Route::get('/assets/scripts', [AssetController::class, 'scripts'])->name('assets.scripts');
    Route::get('/assets/styles', [AssetController::class, 'styles'])->name('assets.styles');
});
