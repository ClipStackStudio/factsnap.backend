<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
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

    // Firebase Authentication sign-in (supports phone and anonymous auth) with rate limiting
    Route::post('/auth/firebase/sign-in', [FirebaseAuthController::class, 'signInWithIdToken'])
        ->middleware('throttle:20,1');

    // Unified user package management routes (supports Firebase authentication for both regular and guest users)
    Route::middleware('auth.firebase')->group(function () {
        Route::get('/me', [FirebaseAuthController::class, 'me']);
        
        // Phone verification for existing users (guest -> verified conversion)
        Route::post('/auth/firebase/verify-phone', [FirebaseAuthController::class, 'verifyPhoneNumber'])
            ->middleware('throttle:10,1');
        
        // Package management routes for authenticated users (Firebase regular and guest users)
        Route::prefix('user')->group(function() {
            Route::get('/packages', [UserPackageController::class, 'index']);
            Route::post('/packages/{packageId}/subscribe', [UserPackageController::class, 'subscribe']);
            Route::delete('/packages/{packageId}/unsubscribe', [UserPackageController::class, 'unsubscribe']);
            Route::get('/packages/{packageId}/status', [UserPackageController::class, 'status']);
            
            // Delivery settings management
            Route::get('/packages/{packageId}/delivery-settings', [UserPackageController::class, 'getDeliverySettings']);
            Route::put('/packages/{packageId}/delivery-settings', [UserPackageController::class, 'updateDeliverySettings']);
            
            // Notification rules and capabilities
            Route::get('/notification-rules', [UserPackageController::class, 'getNotificationRules']);

            // Push notification management
            Route::post('/push-token', [\App\Http\Controllers\PushNotificationController::class, 'registerDeviceToken']);
            Route::get('/push-token', [\App\Http\Controllers\PushNotificationController::class, 'getPushToken']);
            Route::delete('/push-token', [\App\Http\Controllers\PushNotificationController::class, 'deactivatePushToken']);
            Route::put('/push-preferences', [\App\Http\Controllers\PushNotificationController::class, 'updatePreferences']);
            
            // Enhanced notification preferences
            Route::get('/notification-preferences', [\App\Http\Controllers\PushNotificationController::class, 'getNotificationPreferences']);
            Route::put('/notification-preferences', [\App\Http\Controllers\PushNotificationController::class, 'updateNotificationPreferences']);
            
            // Delivery status and history
            Route::get('/delivery-status', [\App\Http\Controllers\PushNotificationController::class, 'getDeliveryStatus']);
            Route::get('/delivered-facts', [\App\Http\Controllers\PushNotificationController::class, 'getDeliveredFacts']);
            Route::patch('/delivered-facts/{id}/seen', [\App\Http\Controllers\PushNotificationController::class, 'markAsSeen']);
        });
    });

    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/auth/logout', [ProfileController::class, 'logout']);
        Route::get('/protected/ping', function() { return ['message' => 'pong']; }); // simple protected test
        
        // Admin routes for cache management
        Route::post('/categories/clear-cache', [CategoryController::class, 'clearCache']);
    });

    // Firebase phone authentication routes
    Route::prefix('auth/firebase')->group(function() {
        Route::post('/phone/verify', [FirebaseAuthController::class, 'verifyToken']);
    });
});
