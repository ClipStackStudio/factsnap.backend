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
     *   path="/user/push-token",
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
     *   path="/user/push-preferences",
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
     *   path="/user/delivered-facts",
     *   tags={"Push Notifications"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Get delivered facts history",
     *   description="Get paginated history of facts delivered to the authenticated user.",
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
     *   @OA\Response(
     *     response=200,
     *     description="Delivered facts history",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object")
     *     )
     *   )
     * )
     */
    public function getDeliveredFacts(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->get('per_page', 20), 100);

        $deliveredFacts = DeliveredFact::with(['fact', 'package'])
            ->where('user_id', $user->id)
            ->orderBy('delivered_at', 'desc')
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
     *   path="/user/delivered-facts/{id}/seen",
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
}
