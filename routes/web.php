<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
*/

// Include Auth Routes BEFORE SPA routes
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'user_role'])->get('admin/{any?}', function () {
    return view('admin.app');
})->where('any', '.*')->name('admin.spa');

Route::get('og-image/poetry/{slug}', [\App\Http\Controllers\OgImageController::class, 'generatePoetryImage'])->name('og.poetry');

Route::get('{any?}', [\App\Http\Controllers\SpaController::class, 'index'])->where('any', '^(?!admin|api).*$')->name('web.spa');


Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

