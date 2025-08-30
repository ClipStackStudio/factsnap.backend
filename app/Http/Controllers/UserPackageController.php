<?php

namespace App\Http\Controllers;

use App\Enums\ErrorCode;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class UserPackageController extends Controller
{
    use ApiErrorResponses;
    /**
     * @OA\Get(
     *     path="/api/user/packages",
     *     summary="Get user's subscribed packages",
     *     description="Retrieve all packages that the authenticated user has subscribed to. Supports Firebase authentication for both regular users (phone auth) and guest users (anonymous auth).",
     *     operationId="getUserPackages",
     *     tags={"User Packages"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="icon_url", type="string"),
     *                 @OA\Property(property="access_level", type="string"),
     *                 @OA\Property(property="subscribed_at", type="string", format="date-time"),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized - Invalid or missing Firebase ID token", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        $packages = $user->packages()->with('category')->get()->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'icon_url' => $package->icon_url,
                'access_level' => $package->access_level,
                'subscribed_at' => $package->pivot->subscribed_at,
                'category' => [
                    'id' => $package->category->id,
                    'name' => $package->category->name,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $packages
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/packages/{packageId}/subscribe",
     *     summary="Subscribe to a package",
     *     description="Subscribe the authenticated user to a specific package. Supports Firebase authentication for both regular users (phone auth) and guest users (anonymous auth). Access levels: 'free' (all users), 'loggedIn' (Firebase authenticated users), 'premium' (premium users only).",
     *     operationId="subscribeToPackage",
     *     tags={"User Packages"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="packageId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="UUID of the package to subscribe to"
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successfully subscribed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully subscribed to package: Package Name"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="package_id", type="string", format="uuid"),
     *                 @OA\Property(property="package_name", type="string"),
     *                 @OA\Property(property="subscribed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Already subscribed", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=403, description="Access denied - user access level insufficient for package", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=404, description="Package not found", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=401, description="Unauthorized - Invalid or missing Firebase ID token", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function subscribe(Request $request, string $packageId): JsonResponse
    {
        $user = Auth::user();
        $rulesService = app(\App\Services\NotificationRulesService::class);
        
        try {
            $package = Package::findOrFail($packageId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('package', $packageId);
        }
        
        // Check if user has access to this package
        if (!$this->hasAccess($user, $package)) {
            $userLevel = $user->is_guest ? 'guest' : ($user->is_premium ? 'premium' : 'loggedIn');
            return $this->accessDeniedResponse($userLevel, $package->access_level);
        }
        
        // Check if already subscribed
        if ($user->packages()->where('packages.id', $packageId)->exists()) {
            return $this->errorResponse(
                ErrorCode::ALREADY_SUBSCRIBED,
                "User ID '{$user->id}' is already subscribed to package ID '{$packageId}'"
            );
        }

        // Apply default settings for user type
        $defaultSettings = $rulesService->getDefaultSettings($user);
        
        // Validate and merge delivery settings
        try {
            $deliverySettings = $request->validate([
                'delivery_enabled' => ['sometimes', 'boolean'],
                'delivery_mode' => ['sometimes', 'string'],
                'preferred_times' => ['sometimes', 'array'],
                'preferred_times.*' => ['string', 'date_format:H:i'],
                'days_of_week' => ['sometimes', 'array'],
                'days_of_week.*' => ['integer', 'min:0', 'max:6'],
                'delivery_window_start' => ['sometimes', 'date_format:H:i'],
                'delivery_window_end' => ['sometimes', 'date_format:H:i'],
                'per_day_quota' => ['sometimes', 'integer', 'min:1'],
                'min_interval_minutes' => ['sometimes', 'integer', 'min:1'],
            ]);
            
            // Merge with defaults
            $deliverySettings = array_merge($defaultSettings, $deliverySettings);
            
            // Validate against user rules
            $validationErrors = $rulesService->validateDeliverySettings($user, $deliverySettings);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid delivery settings',
                    'errors' => $validationErrors
                ], 422);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Invalid delivery settings.');
        }
        
        $subscribedAt = now();
        $nextDelivery = $this->calculateNextDeliveryTime($deliverySettings, $user);
        
        $user->packages()->attach($packageId, [
            'subscribed_at' => $subscribedAt,
            'delivery_enabled' => $deliverySettings['delivery_enabled'] ?? true,
            'delivery_mode' => $deliverySettings['delivery_mode'] ?? 'daily',
            'preferred_times' => json_encode($deliverySettings['preferred_times'] ?? ['10:00']),
            'days_of_week' => isset($deliverySettings['days_of_week']) ? json_encode($deliverySettings['days_of_week']) : null,
            'delivery_window_start' => $deliverySettings['delivery_window_start'] ?? null,
            'delivery_window_end' => $deliverySettings['delivery_window_end'] ?? null,
            'per_day_quota' => $deliverySettings['per_day_quota'] ?? 1,
            'min_interval_minutes' => $deliverySettings['min_interval_minutes'] ?? null,
            'time_sensitive' => $deliverySettings['time_sensitive'] ?? false,
            'next_delivery_at' => $nextDelivery,
        ]);

        // Schedule first fact delivery if enabled
        if (($deliverySettings['delivery_enabled'] ?? true) && $user->push_notifications_enabled && $user->apns_device_token) {
            \App\Jobs\DeliverFactJob::dispatch($user, $package)->delay($nextDelivery);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to package: ' . $package->name,
            'data' => [
                'package_id' => $package->id,
                'package_name' => $package->name,
                'subscribed_at' => $subscribedAt,
                'delivery_settings' => array_merge($deliverySettings, ['next_delivery_at' => $nextDelivery])
            ]
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/user/packages/{packageId}/unsubscribe",
     *     summary="Unsubscribe from a package",
     *     description="Unsubscribe the authenticated user from a specific package. Supports Firebase authentication for both regular users (phone auth) and guest users (anonymous auth).",
     *     operationId="unsubscribeFromPackage",
     *     tags={"User Packages"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="packageId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="UUID of the package to unsubscribe from"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully unsubscribed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully unsubscribed from package: Package Name"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="package_id", type="string", format="uuid"),
     *                 @OA\Property(property="package_name", type="string"),
     *                 @OA\Property(property="unsubscribed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Not subscribed to this package", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=404, description="Package not found", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=401, description="Unauthorized - Invalid or missing Firebase ID token", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function unsubscribe(Request $request, string $packageId): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $package = Package::findOrFail($packageId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('package', $packageId);
        }
        
        // Check if subscribed
        if (!$user->packages()->where('packages.id', $packageId)->exists()) {
            return $this->errorResponse(
                ErrorCode::NOT_SUBSCRIBED,
                "User ID '{$user->id}' is not subscribed to package ID '{$packageId}'"
            );
        }
        
        $user->packages()->detach($packageId);
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully unsubscribed from package: ' . $package->name,
            'data' => [
                'package_id' => $package->id,
                'package_name' => $package->name,
                'unsubscribed_at' => now()
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/packages/{packageId}/status",
     *     summary="Check subscription status",
     *     description="Check if the authenticated user is subscribed to a specific package and whether they can subscribe. Supports Firebase authentication for both regular users (phone auth) and guest users (anonymous auth).",
     *     operationId="getSubscriptionStatus",
     *     tags={"User Packages"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="packageId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="UUID of the package to check subscription status for"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subscription status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_subscribed", type="boolean", description="Whether user is subscribed to this package"),
     *                 @OA\Property(property="can_subscribe", type="boolean", description="Whether user has access level to subscribe to this package"),
     *                 @OA\Property(property="subscribed_at", type="string", format="date-time", nullable=true, description="When user subscribed (null if not subscribed)"),
     *                 @OA\Property(property="package_name", type="string", description="Name of the package"),
     *                 @OA\Property(property="access_level_required", type="string", description="Required access level for this package", enum={"free", "loggedIn", "premium"})
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Package not found", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=401, description="Unauthorized - Invalid or missing Firebase ID token", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function status(Request $request, string $packageId): JsonResponse
    {
        $user = Auth::user();
        
        try {
            $package = Package::findOrFail($packageId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('package', $packageId);
        }
        
        $subscription = $user->packages()->where('packages.id', $packageId)->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'is_subscribed' => $subscription !== null,
                'can_subscribe' => $this->hasAccess($user, $package),
                'subscribed_at' => $subscription ? $subscription->pivot->subscribed_at : null,
                'package_name' => $package->name,
                'access_level_required' => $package->access_level
            ]
        ]);
    }

    /**
     * Check if user has access to a package based on access level
     */
    private function hasAccess(User $user, Package $package): bool
    {
        switch ($package->access_level) {
            case 'free':
                return true;
            case 'loggedIn':
                // Only verified Firebase users (exclude guest users)
                return !empty($user->firebase_uid) && !$user->is_guest;
            case 'premium':
                return $user->is_premium;
            default:
                return false;
        }
    }

    /**
     * Calculate next delivery time based on new notification rules
     */
    private function calculateNextDeliveryTime(array $settings, \App\Models\User $user): ?\Carbon\Carbon
    {
        $mode = $settings['delivery_mode'] ?? 'daily';
        $preferredTimes = $settings['preferred_times'] ?? ['10:00'];
        $daysOfWeek = $settings['days_of_week'] ?? null;
        $userTimezone = $user->timezone ?? 'UTC';

        $now = \Carbon\Carbon::now($userTimezone);
        $rulesService = app(\App\Services\NotificationRulesService::class);

        switch ($mode) {
            case 'daily':
                $time = $preferredTimes[0];
                [$hour, $minute] = explode(':', $time);
                $next = $now->copy()->addDay();
                $next->setTime((int)$hour, (int)$minute, 0);
                break;

            case 'weekly':
                $time = $preferredTimes[0];
                $days = $daysOfWeek ?? [1, 2, 3, 4, 5]; // Default to weekdays
                
                $next = $now->copy()->addDay();
                while (!in_array($next->dayOfWeek, $days)) {
                    $next->addDay();
                }
                
                [$hour, $minute] = explode(':', $time);
                $next->setTime((int)$hour, (int)$minute, 0);
                break;

            case 'times_per_day':
                // Schedule for the first preferred time
                $time = $preferredTimes[0];
                [$hour, $minute] = explode(':', $time);
                $next = $now->copy()->addDay();
                $next->setTime((int)$hour, (int)$minute, 0);
                break;

            case 'windowed':
                $windowStart = $settings['delivery_window_start'] ?? '09:00';
                $windowEnd = $settings['delivery_window_end'] ?? '18:00';
                
                [$startHour, $startMinute] = explode(':', $windowStart);
                [$endHour, $endMinute] = explode(':', $windowEnd);
                
                $windowStartTime = $now->copy()->addDay()->setTime((int)$startHour, (int)$startMinute, 0);
                $windowEndTime = $now->copy()->addDay()->setTime((int)$endHour, (int)$endMinute, 0);
                
                // Random time within the window
                $randomMinutes = rand(0, $windowEndTime->diffInMinutes($windowStartTime));
                $next = $windowStartTime->addMinutes($randomMinutes);
                break;

            default:
                return null;
        }

        // Ensure it's not within quiet hours
        $next = $rulesService->getNextAllowedTime($user, $next);
        
        // Apply jitter
        $next = $rulesService->applyJitter($next);

        return $next->utc();
    }
}
