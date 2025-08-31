<?php

namespace App\Http\Controllers;

use App\Models\DeliveredFact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

class PushNotificationController extends Controller
{
    use ApiErrorResponses;

    /**
     * Register device token for push notifications.
     * 
     * @OA\Post(
     *   path="/api/user/push-token",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Register device token for push notifications",
     *   description="Register or update the Apple Push Notification device token for the authenticated user.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"device_token"},
     *       @OA\Property(property="device_token", type="string", description="Apple Push Notification device token"),
     *       @OA\Property(property="enabled", type="boolean", description="Enable/disable push notifications", example=true)
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Device token registered successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Device token registered successfully")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   )
     * )
     */
    public function registerDeviceToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'device_token' => ['required', 'string', 'min:10'],
                'enabled' => ['sometimes', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Invalid device token data.');
        }

        $user = $request->user();
        $user->update([
            'apns_device_token' => $validated['device_token'],
            'push_notifications_enabled' => $validated['enabled'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully',
        ]);
    }

    /**
     * Update push notification preferences.
     * 
     * @OA\Put(
     *   path="/api/user/push-preferences",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Update push notification preferences",
     *   description="Update global push notification preferences for the authenticated user.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="enabled", type="boolean", description="Enable/disable all push notifications"),
     *       @OA\Property(property="quiet_hours_start", type="string", format="time", example="22:00", description="Start of quiet hours (24h format)"),
     *       @OA\Property(property="quiet_hours_end", type="string", format="time", example="08:00", description="End of quiet hours (24h format)")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Preferences updated successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Push notification preferences updated"),
     *       @OA\Property(property="data", type="object")
     *     )
     *   )
     * )
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'enabled' => ['sometimes', 'boolean'],
                'quiet_hours_start' => ['sometimes', 'date_format:H:i'],
                'quiet_hours_end' => ['sometimes', 'date_format:H:i'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Invalid preference data.');
        }

        $user = $request->user();
        
        if (isset($validated['enabled'])) {
            $user->push_notifications_enabled = $validated['enabled'];
        }

        $preferences = $user->notification_preferences ?? [];
        if (isset($validated['quiet_hours_start'])) {
            $preferences['quiet_hours_start'] = $validated['quiet_hours_start'];
        }
        if (isset($validated['quiet_hours_end'])) {
            $preferences['quiet_hours_end'] = $validated['quiet_hours_end'];
        }

        $user->notification_preferences = $preferences;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Push notification preferences updated',
            'data' => [
                'enabled' => $user->push_notifications_enabled,
                'preferences' => $user->notification_preferences,
            ],
        ]);
    }

    /**
     * Get delivered facts history.
     * 
     * @OA\Get(
     *   path="/api/user/delivered-facts",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Get delivered facts history",
     *   description="Get paginated history of facts delivered to the authenticated user with package information and delivery metadata.",
     *   @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number",
     *     @OA\Schema(type="integer", minimum=1, example=1)
     *   ),
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     description="Items per page",
     *     @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *   ),
     *   @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="Filter by delivery status",
     *     @OA\Schema(type="string", enum={"scheduled", "delivering", "delivered", "failed", "failed_permanently"})
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Delivered facts history",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="current_page", type="integer"),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/DeliveredFact")),
     *         @OA\Property(property="first_page_url", type="string"),
     *         @OA\Property(property="from", type="integer"),
     *         @OA\Property(property="last_page", type="integer"),
     *         @OA\Property(property="last_page_url", type="string"),
     *         @OA\Property(property="links", type="array", @OA\Items(type="object")),
     *         @OA\Property(property="next_page_url", type="string"),
     *         @OA\Property(property="path", type="string"),
     *         @OA\Property(property="per_page", type="integer"),
     *         @OA\Property(property="prev_page_url", type="string"),
     *         @OA\Property(property="to", type="integer"),
     *         @OA\Property(property="total", type="integer")
     *       )
     *     )
     *   )
     * )
     */
    public function getDeliveredFacts(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->get('per_page', 20), 100);
        $status = $request->get('status');

        $query = DeliveredFact::with(['fact', 'package'])
            ->where('user_id', $user->id);
            
        if ($status) {
            $query->where('status', $status);
        }
            
        $deliveredFacts = $query->orderBy('delivered_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $deliveredFacts,
        ]);
    }

    /**
     * Mark delivered fact as seen.
     * 
     * @OA\Patch(
     *   path="/api/user/delivered-facts/{id}/seen",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Mark delivered fact as seen",
     *   description="Mark a delivered fact as seen by the user.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="Delivered fact ID",
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Fact marked as seen",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Fact marked as seen")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Delivered fact not found",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   )
     * )
     */
    public function markAsSeen(Request $request, string $deliveredFactId): JsonResponse
    {
        $user = $request->user();

        $deliveredFact = DeliveredFact::where('id', $deliveredFactId)
            ->where('user_id', $user->id)
            ->first();

        if (!$deliveredFact) {
            return $this->notFoundResponse('delivered_fact', $deliveredFactId);
        }

        $deliveredFact->update([
            'seen_at' => now(),
            'status' => 'seen',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fact marked as seen',
        ]);
    }

    /**
     * Update comprehensive push notification preferences.
     * 
     * @OA\Put(
     *   path="/api/user/notification-preferences",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Update comprehensive notification preferences",
     *   description="Update all push notification preferences including timezone, quiet hours, and global settings. Settings are validated based on user type capabilities.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/PushNotificationPreferences")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Preferences updated successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Notification preferences updated successfully"),
     *       @OA\Property(property="data", ref="#/components/schemas/PushNotificationPreferences")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error - invalid timezone or quiet hours format",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   )
     * )
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        $rulesService = app(\App\Services\NotificationRulesService::class);
        
        try {
            $validated = $request->validate([
                'push_notifications_enabled' => ['sometimes', 'boolean'],
                'timezone' => ['sometimes', 'string', 'timezone'],
                'quiet_hours_enabled' => ['sometimes', 'boolean'],
                'quiet_hours_start' => ['sometimes', 'date_format:H:i'],
                'quiet_hours_end' => ['sometimes', 'date_format:H:i'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Invalid notification preference data.');
        }

        $user = $request->user();
        
        // Validate quiet hours settings based on user type
        if (isset($validated['quiet_hours_enabled']) && !$validated['quiet_hours_enabled']) {
            $userType = $rulesService->getUserType($user);
            $rules = $rulesService->getRulesForUserType($userType);
            
            if (!$rules['can_disable_quiet_hours']) {
                return response()->json([
                    'success' => false,
                    'message' => 'User type does not support disabling quiet hours',
                    'errors' => ['quiet_hours_enabled' => ['Cannot disable quiet hours for your account type']]
                ], 422);
            }
        }
        
        // Update user fields
        $updateData = [];
        if (isset($validated['push_notifications_enabled'])) {
            $updateData['push_notifications_enabled'] = $validated['push_notifications_enabled'];
        }
        if (isset($validated['timezone'])) {
            $updateData['timezone'] = $validated['timezone'];
        }
        if (isset($validated['quiet_hours_enabled'])) {
            $updateData['quiet_hours_enabled'] = $validated['quiet_hours_enabled'];
        }
        if (isset($validated['quiet_hours_start'])) {
            $updateData['quiet_hours_start'] = $validated['quiet_hours_start'];
        }
        if (isset($validated['quiet_hours_end'])) {
            $updateData['quiet_hours_end'] = $validated['quiet_hours_end'];
        }
        
        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'data' => [
                'push_notifications_enabled' => $user->push_notifications_enabled,
                'timezone' => $user->timezone,
                'quiet_hours_enabled' => $user->quiet_hours_enabled,
                'quiet_hours_start' => $user->quiet_hours_start,
                'quiet_hours_end' => $user->quiet_hours_end,
            ],
        ]);
    }

    /**
     * Get user's push notification preferences.
     * 
     * @OA\Get(
     *   path="/api/user/notification-preferences",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Get current notification preferences",
     *   description="Get all push notification preferences for the authenticated user including timezone and quiet hours settings.",
     *   @OA\Response(
     *     response=200,
     *     description="Current notification preferences",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", ref="#/components/schemas/PushNotificationPreferences")
     *     )
     *   )
     * )
     */
    public function getNotificationPreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'push_notifications_enabled' => $user->push_notifications_enabled,
                'timezone' => $user->timezone,
                'quiet_hours_enabled' => $user->quiet_hours_enabled,
                'quiet_hours_start' => $user->quiet_hours_start,
                'quiet_hours_end' => $user->quiet_hours_end,
            ],
        ]);
    }

    /**
     * Get delivery status and statistics.
     * 
     * @OA\Get(
     *   path="/api/user/delivery-status",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Get delivery status and statistics",
     *   description="Get comprehensive delivery status including today's quota usage, next scheduled deliveries, and delivery statistics.",
     *   @OA\Parameter(
     *     name="date",
     *     in="query",
     *     description="Date to check status for (YYYY-MM-DD format, defaults to today)",
     *     @OA\Schema(type="string", format="date", example="2024-01-15")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Delivery status and statistics",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="today_facts_delivered", type="integer", description="Facts delivered today"),
     *         @OA\Property(property="daily_quota_used", type="integer", description="Total daily quota used"),
     *         @OA\Property(property="daily_quota_limit", type="integer", description="Daily quota limit for user type"),
     *         @OA\Property(property="can_schedule_more", type="boolean", description="Whether more facts can be scheduled today"),
     *         @OA\Property(property="next_delivery_at", type="string", format="date-time", description="Next scheduled delivery time"),
     *         @OA\Property(property="subscribed_packages", type="integer", description="Number of subscribed packages"),
     *         @OA\Property(property="packages_with_delivery", type="integer", description="Packages with delivery enabled"),
     *         @OA\Property(property="user_type", type="string", enum={"guest", "logged_in", "premium"}, description="Current user type"),
     *         @OA\Property(property="package_quotas", type="array", @OA\Items(type="object",
     *           @OA\Property(property="package_id", type="string", format="uuid"),
     *           @OA\Property(property="package_name", type="string"),
     *           @OA\Property(property="delivered_today", type="integer"),
     *           @OA\Property(property="quota_limit", type="integer"),
     *           @OA\Property(property="next_delivery_at", type="string", format="date-time")
     *         ))
     *       )
     *     )
     *   )
     * )
     */
    public function getDeliveryStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $rulesService = app(\App\Services\NotificationRulesService::class);
        $date = $request->get('date', now()->toDateString());
        
        try {
            $checkDate = \Carbon\Carbon::parse($date)->startOfDay();
        } catch (\Exception $e) {
            return $this->validationErrorResponse(['date' => ['Invalid date format']], 'Invalid date format.');
        }
        
        $userType = $rulesService->getUserType($user);
        $rules = $rulesService->getRulesForUserType($userType);
        
        // Get today's delivered facts count
        $todayFactsCount = DeliveredFact::where('user_id', $user->id)
            ->whereBetween('delivered_at', [$checkDate, $checkDate->copy()->endOfDay()])
            ->count();
        
        // Check if can schedule more
        $canScheduleMore = $rulesService->canScheduleMore($user, $checkDate);
        
        // Get next delivery time
        $nextDelivery = $user->packages()
            ->where('delivery_enabled', true)
            ->orderBy('next_delivery_at')
            ->first();
        
        // Get subscribed packages info
        $subscribedPackages = $user->packages()->count();
        $packagesWithDelivery = $user->packages()->where('delivery_enabled', true)->count();
        
        // Get per-package quotas
        $packageQuotas = $user->packages()->with('package')->get()->map(function ($subscription) use ($checkDate) {
            $pivotData = $subscription->pivot;
            $deliveredToday = DeliveredFact::where('user_id', $subscription->pivot->user_id)
                ->where('package_id', $subscription->id)
                ->whereBetween('delivered_at', [$checkDate, $checkDate->copy()->endOfDay()])
                ->count();
            
            return [
                'package_id' => $subscription->id,
                'package_name' => $subscription->name,
                'delivered_today' => $deliveredToday,
                'quota_limit' => $pivotData->per_day_quota,
                'next_delivery_at' => $pivotData->next_delivery_at,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'today_facts_delivered' => $todayFactsCount,
                'daily_quota_used' => $todayFactsCount,
                'daily_quota_limit' => $rules['max_daily_facts'],
                'can_schedule_more' => $canScheduleMore,
                'next_delivery_at' => $nextDelivery ? $nextDelivery->pivot->next_delivery_at : null,
                'subscribed_packages' => $subscribedPackages,
                'packages_with_delivery' => $packagesWithDelivery,
                'user_type' => $userType,
                'package_quotas' => $packageQuotas,
            ]
        ]);
    }
}
