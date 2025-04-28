<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.sanctum')->group(function () {
    Route::get('/me', function () {
        return auth()->user();
    });
});
