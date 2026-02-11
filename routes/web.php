<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
*/
Route::get('admin/{any?}', function () {
    return view('admin.app');
})->where('any', '.*')->name('admin.spa');

Route::get('og-image/poetry/{slug}', [\App\Http\Controllers\OgImageController::class, 'generatePoetryImage'])->name('og.poetry');

Route::get('{any?}', [\App\Http\Controllers\SpaController::class, 'index'])->where('any', '^(?!admin|api).*$')->name('web.spa');


require __DIR__ . '/auth.php';