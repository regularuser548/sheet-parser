<?php

use App\Http\Controllers\Sheets\ImportMappingController;
use App\Http\Controllers\Sheets\ImportRunController;
use App\Http\Controllers\Sheets\SheetSourceController;
use App\Http\Controllers\Sheets\SheetTabController;
use App\Http\Controllers\Sheets\SheetValidationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('sheets')->name('sheets.')->group(function () {
    Route::get('sources', [SheetSourceController::class, 'index'])->name('sources.index');
    Route::get('sources/create', [SheetSourceController::class, 'create'])->name('sources.create');
    Route::post('sources', [SheetSourceController::class, 'store'])->name('sources.store');
    Route::get('sources/{source}/edit', [SheetSourceController::class, 'edit'])->name('sources.edit');
    Route::put('sources/{source}', [SheetSourceController::class, 'update'])->name('sources.update');
    Route::delete('sources/{source}', [SheetSourceController::class, 'destroy'])->name('sources.destroy');
    Route::post('sources/{source}/import', [SheetSourceController::class, 'import'])->name('sources.import');

    Route::post('sources/{source}/tabs', [SheetTabController::class, 'store'])->name('tabs.store');
    Route::put('tabs/{tab}', [SheetTabController::class, 'update'])->name('tabs.update');
    Route::delete('tabs/{tab}', [SheetTabController::class, 'destroy'])->name('tabs.destroy');

    Route::get('tabs/{tab}/mappings', [ImportMappingController::class, 'index'])->name('mappings.index');
    Route::post('tabs/{tab}/mappings', [ImportMappingController::class, 'store'])->name('mappings.store');
    Route::put('mappings/{mapping}', [ImportMappingController::class, 'update'])->name('mappings.update');
    Route::delete('mappings/{mapping}', [ImportMappingController::class, 'destroy'])->name('mappings.destroy');

    Route::get('runs', [ImportRunController::class, 'index'])->name('runs.index');

    Route::post('validate', [SheetValidationController::class, 'store'])->name('validate');
});
