<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GuestController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\UserPackageController;
use App\Http\Controllers\FirebaseAuthController;
use OpenApi\Annotations as OA;

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

    // Firebase Phone Auth sign-in with rate limiting
    Route::post('/auth/firebase/sign-in', [FirebaseAuthController::class, 'signInWithIdToken'])
        ->middleware('throttle:20,1');

    // Unified user package management routes (supports both Firebase and Sanctum authentication)
    Route::middleware('dual.auth')->group(function () {
        Route::get('/me', [FirebaseAuthController::class, 'me']);
        
        // Unified package management routes for both Firebase and guest users
        Route::prefix('user')->group(function() {
            Route::get('/packages', [UserPackageController::class, 'index']);
            Route::post('/packages/{packageId}/subscribe', [UserPackageController::class, 'subscribe']);
            Route::delete('/packages/{packageId}/unsubscribe', [UserPackageController::class, 'unsubscribe']);
            Route::get('/packages/{packageId}/status', [UserPackageController::class, 'status']);
        });
    });

    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/auth/logout', [ProfileController::class, 'logout']);
        Route::get('/protected/ping', function() { return ['message' => 'pong']; }); // simple protected test
        
        // Guest token refresh (Sanctum only)
        Route::post('/auth/guest/refresh-token', [GuestController::class, 'refreshToken']);
        
        // Admin routes for cache management
        Route::post('/categories/clear-cache', [CategoryController::class, 'clearCache']);
    });

    // Firebase phone authentication routes
    Route::prefix('auth/firebase')->group(function() {
        Route::post('/phone/verify', [FirebaseAuthController::class, 'verifyToken']);
    });
});
