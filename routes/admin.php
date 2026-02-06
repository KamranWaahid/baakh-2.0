<?php

use Illuminate\Support\Facades\Route;

/**
 * Admin React SPA Route
 * This catch-all route serves the new React-based admin interface.
 */
Route::get('{any?}', function () {
    return view('admin.app');
})->where('any', '.*')->name('admin.spa');