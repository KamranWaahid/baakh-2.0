<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('poets', \App\Http\Controllers\Api\Admin\PoetController::class);
        Route::get('poetry/create', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'create']);
        Route::apiResource('poetry', \App\Http\Controllers\Api\Admin\PoetryController::class);
        Route::get('couplets', [\App\Http\Controllers\Api\Admin\CoupletController::class, 'index']);
        Route::get('dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
        Route::patch('poetry/{id}/toggle-visibility', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'toggleVisibility']);
        Route::patch('poetry/{id}/toggle-featured', [\App\Http\Controllers\Api\Admin\PoetryController::class, 'toggleFeatured']);
    });
