<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug/trigger-error', function () {
    throw new Exception('This is a test system error for verification.');
});
