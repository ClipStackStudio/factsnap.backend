<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GuestController;
use App\Http\Controllers\Auth\ProfileController;

Route::middleware('api')->group(function() {
    Route::post('/auth/guest', [GuestController::class, 'createGuest']);

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/me', [ProfileController::class, 'me']);
        Route::post('/auth/logout', [ProfileController::class, 'logout']);
        Route::get('/protected/ping', function() { return ['message' => 'pong']; }); // simple protected test
    });
});
