<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\LoginWithGoogleController;
use App\Http\Controllers\Users\BaakhUserProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    /* Route::get('admin/register', [RegisteredUserController::class, 'create'])
                ->name('register'); */

    Route::get('login/with-google', [LoginWithGoogleController::class, 'loginWithGoogle'])->name('login.with-google');
    Route::get('auth/google-callback', [LoginWithGoogleController::class, 'googleAuthorized'])->name('google.authorized-user');

    /* Route::post('register', [RegisteredUserController::class, 'store']); */

    Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])
                ->name('admin.login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    //Route::get('admin/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');

    //Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    //Route::get('admin/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');

    //Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('admin/verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('admin/verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    //Route::get('admin/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    //Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});


Route::middleware(['auth'])->group(function () {
    Route::get('user/profile', [BaakhUserProfileController::class, 'index'])->name('user.profile');
    Route::get('user/edit/profile', [BaakhUserProfileController::class, 'editProfile'])->name('user.profile.edit');
    Route::get('user/favorites/poetry/{slug}', [BaakhUserProfileController::class, 'userFavoritePoetry'])->name('user.favorite.poetry');
    Route::get('user/favorites/{slug}', [BaakhUserProfileController::class, 'userFavorites'])->name('user.favorites');
    Route::post('user/load-items/poetry', [BaakhUserProfileController::class, 'getLimitedPoetryAjax'])->name('user.load-poetry');
    Route::post('user/load-items/others', [BaakhUserProfileController::class, 'getLikedItems'])->name('user.load-items');
});