<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GuestController extends Controller
{
    use \App\Http\Controllers\ApiErrorResponses;

    /**
     * @OA\Post(
     *     path="/api/auth/guest",
     *     summary="Create a guest user",
     *     description="Create a new guest user with a long-lived Sanctum token (10 years expiration). Guest users can subscribe to packages based on their access level.",
     *     operationId="createGuest",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="is_premium", type="boolean", example=false, description="Whether this guest should have premium access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Guest user created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Guest user created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", description="Sanctum authentication token"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="Guest ABC123"),
     *                     @OA\Property(property="is_guest", type="boolean", example=true),
     *                     @OA\Property(property="is_premium", type="boolean", example=false),
     *                     @OA\Property(property="abilities", type="array", @OA\Items(type="string", enum={"guest", "premium"}))
     *                 ),
     *                 @OA\Property(property="token_expires_at", type="string", format="date-time", description="Token expiration date (10 years from now)")
     *             )
     *         )
     *     )
     * )
     */
    public function createGuest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_premium' => 'sometimes|boolean'
        ]);

        $user = User::create([
            'name' => 'Guest '.str()->random(6),
            'email' => str()->uuid().'@guest.local',
            'password' => bcrypt(str()->random(32)),
            'is_guest' => true,
            'is_premium' => $data['is_premium'] ?? false,
        ]);

        $abilities = $user->is_premium ? ['premium'] : ['guest'];
        
        // Create token with 10-year expiration
        $token = $user->createToken('guest-token', $abilities, now()->addYears(10))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Guest user created successfully',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_guest' => $user->is_guest,
                    'is_premium' => $user->is_premium,
                    'abilities' => $abilities,
                ],
                'token_expires_at' => now()->addYears(10)->toISOString()
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/guest/refresh-token",
     *     summary="Refresh guest user token",
     *     description="Refresh the Sanctum token for a guest user. This revokes the current token and issues a new one with 10 years expiration. Only available for guest users.",
     *     operationId="refreshGuestToken",
     *     tags={"Authentication"},
     *     security={{"sanctumAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", description="New Sanctum authentication token"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="is_guest", type="boolean", example=true),
     *                     @OA\Property(property="is_premium", type="boolean"),
     *                     @OA\Property(property="abilities", type="array", @OA\Items(type="string", enum={"guest", "premium"}))
     *                 ),
     *                 @OA\Property(property="token_expires_at", type="string", format="date-time", description="New token expiration date (10 years from now)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Access denied - only available for guest users", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=401, description="Unauthorized - Invalid Sanctum token", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    /**
     * Refresh the guest user's token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Ensure this is a guest user
        if (!$user->is_guest) {
            return $this->errorResponse(
                \App\Enums\ErrorCode::ACCESS_DENIED,
                'Token refresh is only available for guest users'
            );
        }

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token with 10-year expiration
        $abilities = $user->is_premium ? ['premium'] : ['guest'];
        $token = $user->createToken('guest-token', $abilities, now()->addYears(10))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_guest' => $user->is_guest,
                    'is_premium' => $user->is_premium,
                    'abilities' => $abilities,
                ],
                'token_expires_at' => now()->addYears(10)->toISOString()
            ]
        ]);
    }
}
