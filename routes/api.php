<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GuestController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PackageController;

Route::middleware('api')->group(function() {
    // Public routes - no authentication required
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/all', [CategoryController::class, 'allCategories']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/categories/{id}/packages', [CategoryController::class, 'packages']);
    Route::get('/categories/filter/access-level', [CategoryController::class, 'byAccessLevel']);
    
    Route::get('/packages', [PackageController::class, 'index']);
    Route::get('/packages/{id}', [PackageController::class, 'show']);
    Route::get('/packages/{id}/facts', [PackageController::class, 'facts']);
    Route::get('/packages/{id}/sample-facts', [PackageController::class, 'sampleFacts']);
    Route::get('/packages/filter/access-level', [PackageController::class, 'byAccessLevel']);

    // Auth routes
    Route::post('/auth/guest', [GuestController::class, 'createGuest']);

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/me', [ProfileController::class, 'me']);
        Route::post('/auth/logout', [ProfileController::class, 'logout']);
        Route::get('/protected/ping', function() { return ['message' => 'pong']; }); // simple protected test
        
        // Admin routes for cache management
        Route::post('/categories/clear-cache', [CategoryController::class, 'clearCache']);
    });
});
