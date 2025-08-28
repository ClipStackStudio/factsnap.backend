<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Factory;
use OpenApi\Annotations as OA;

class FirebaseAuthController extends Controller
{
    use ApiErrorResponses;

    private function firebaseAuth(): FirebaseAuthContract
    {
        $credentials = (string) env('FIREBASE_CREDENTIALS', '');
        if ($credentials !== '') {
            $factory = new Factory();
            $trimmed = trim($credentials);
            if ($trimmed !== '' && str_starts_with($trimmed, '{')) {
                $data = json_decode($credentials, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $factory = $factory->withServiceAccount($data);
                }
            } else {
                $factory = $factory->withServiceAccount($credentials);
            }
            return $factory->createAuth();
        }

        /** @var FirebaseAuthContract */
        return app('firebase.auth');
    }

    /**
     * Sign in with Firebase Phone Auth ID token (creates user on first sign-in).
     *
     * **Client Behavior:**
     * 1. Complete phone number verification with the Firebase SDK to obtain an ID token
     * 2. Call this endpoint with that ID token in the JSON body
     * 3. For subsequent authenticated requests, send `Authorization: Bearer <idToken>`
     * 4. Refresh expired ID tokens using the Firebase SDK (API does not issue its own tokens)
     * 5. This endpoint is rate-limited to mitigate abuse (20 requests per minute)
     *
     * @OA\Post(
     *   path="/auth/firebase/sign-in",
     *   tags={"Authentication"},
     *   summary="Sign in with Firebase Phone Auth",
     *   description="Verifies a Firebase ID token (phone auth), upserts a local user, and returns the user profile. Returns 201 on first-time creation and 200 on subsequent sign-ins.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"idToken"},
     *       @OA\Property(
     *         property="idToken", 
     *         type="string", 
     *         description="Firebase ID token obtained after phone authentication",
     *         example="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created – First-time sign-in",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="User created"),
     *       @OA\Property(property="data", ref="#/components/schemas/User")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK – Existing user",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Signed in"),
     *       @OA\Property(property="data", ref="#/components/schemas/User")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized – Invalid/revoked token or not a phone sign-in",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       allOf={
     *         @OA\Schema(ref="#/components/schemas/ApiError"),
     *         @OA\Schema(@OA\Property(property="errors", type="object"))
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=429,
     *     description="Too Many Requests – Rate limit exceeded",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Too Many Attempts.")
     *     )
     *   )
     * )
     */
    public function signInWithIdToken(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'idToken' => ['required', 'string'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Invalid sign-in payload.');
        }

        $auth = $this->firebaseAuth();
        $idToken = $validated['idToken'];

        try {
            $verifiedToken = $auth->verifyIdToken($idToken, true); // checkIfRevoked = true
        } catch (RevokedIdToken|FailedToVerifyToken $e) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Invalid or revoked token.');
        } catch (\Throwable $e) {
            Log::warning('Firebase token verification error', ['error' => $e->getMessage()]);
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Unable to verify token.');
        }

        $claims = $verifiedToken->claims()->all();
        $uid = (string)($claims['sub'] ?? $claims['user_id'] ?? '');
        if ($uid === '') {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Token missing subject.');
        }

        try {
            $fbUser = $auth->getUser($uid);
        } catch (\Throwable $e) {
            return $this->unauthorizedResponse('Firebase user not found for provided token.');
        }

        // Determine if phone sign-in
        $signInProvider = $claims['firebase']['sign_in_provider'] ?? null;
        $phoneNumber = $fbUser->phoneNumber ?? null;
        if ($signInProvider !== 'phone' && empty($phoneNumber)) {
            return $this->unauthorizedResponse('Phone authentication required.');
        }

        $created = false;

        // Try to find existing by firebase_uid
        $user = User::where('firebase_uid', $uid)->first();

        if (!$user && !empty($fbUser->email)) {
            // Link to existing user by email if present
            $user = User::where('email', $fbUser->email)->first();
        }

        if (!$user) {
            $user = new User();
            $created = true;
        }

        $user->firebase_uid = $uid;
        if (!empty($fbUser->displayName)) {
            $user->name = $fbUser->displayName;
        }
        if (!empty($fbUser->email)) {
            $user->email = $fbUser->email;
        }
        if (!empty($fbUser->email) && $fbUser->emailVerified) {
            $user->email_verified_at = $user->email_verified_at ?? now();
        }
        if (!empty($phoneNumber)) {
            $user->phone_number = $phoneNumber; // Already in E.164 format from Firebase
            $user->phone_verified_at = $user->phone_verified_at ?? now();
        }

        // Ensure required fields exist due to schema constraints
        if (empty($user->name)) {
            $user->name = 'User '.substr($uid, -6);
        }
        if (empty($user->email)) {
            $user->email = $uid.'@phone.firebase'; // placeholder unique email
        }
        // Do not require a local password for Firebase login; if column is non-nullable, set a random value that is never used
        if (empty($user->password)) {
            $user->password = Str::random(40);
        }

        $user->save();

        $status = $created ? 201 : 200;
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'firebase_uid' => $user->firebase_uid,
                'phone_number' => $user->phone_number,
                'phone_verified_at' => $user->phone_verified_at,
                'is_guest' => (bool) $user->is_guest,
                'is_premium' => (bool) $user->is_premium,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ], $status);
    }

    /**
     * Get the current authenticated user using a Firebase ID token.
     * 
     * **Client Behavior:**
     * - Send `Authorization: Bearer <idToken>` header with Firebase ID token
     * - Token will be verified on each request and user synced from Firebase
     * 
     * @OA\Get(
     *   path="/me",
     *   tags={"Authentication"},
     *   security={{"bearerAuth": {}}},
     *   summary="Get the current authenticated user",
     *   description="Returns the authenticated user profile. Requires a valid Firebase ID token in Authorization header.",
     *   @OA\Response(
     *     response=200,
     *     description="Current user profile",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", ref="#/components/schemas/User")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized – Invalid/missing token",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }
}
