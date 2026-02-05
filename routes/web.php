<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Admin SPA Route
|--------------------------------------------------------------------------
*/
Route::get('admin/new/{any?}', function () {
    return view('admin.app');
})->where('any', '.*')->name('admin.spa');

/*
|--------------------------------------------------------------------------
| User-Side SPA Route (Revamp)
|--------------------------------------------------------------------------
*/
Route::get('{any?}', function () {
    return view('app');
})->where('any', '^(?!admin/new|api|login|register|password|logout).*$')->name('web.spa');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Auth::routes();
require __DIR__ . '/auth.php';