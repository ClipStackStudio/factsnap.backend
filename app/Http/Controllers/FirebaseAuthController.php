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
     * Sign in a user with Firebase ID token.
     * 
     * **Authentication Flow:**
     * 1. User authenticates via Firebase SDK (phone verification OR anonymous auth)
     * 2. Call this endpoint with Firebase ID token in Authorization header: `Authorization: Bearer <idToken>`
     * 3. For subsequent authenticated requests, continue using the same Authorization header format
     * 4. Refresh expired ID tokens using the Firebase SDK (API does not issue its own tokens)
     * 5. This endpoint is rate-limited to mitigate abuse (20 requests per minute)
     * 
     * **User Types:**
     * - Phone authentication creates regular users with phone verification
     * - Anonymous authentication creates guest users (is_guest = true)
     * @OA\Post(
     *   path="/auth/firebase/sign-in",
     *   tags={"Authentication"},
     *   summary="Sign in with Firebase Authentication",
     *   description="Verifies a Firebase ID token (phone auth or anonymous auth) from Authorization header, upserts a local user, and returns the user profile. Anonymous users are created as guest users. Returns 201 on first-time creation and 200 on subsequent sign-ins.",
     *   @OA\Parameter(
     *     name="Authorization",
     *     in="header",
     *     required=true,
     *     description="Firebase ID token",
     *     @OA\Schema(type="string", example="Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...")
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
     *     description="Unauthorized – Invalid/revoked token or unsupported authentication method",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
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
        // Get the Firebase ID token from the Authorization header
        $authHeader = $request->header('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Authorization header missing or invalid.');
        }
        
        $idToken = substr($authHeader, 7); // Remove 'Bearer ' prefix
        $auth = $this->firebaseAuth();

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

        // Determine authentication type
        $signInProvider = $claims['firebase']['sign_in_provider'] ?? null;
        $phoneNumber = $fbUser->phoneNumber ?? null;
        $isAnonymous = $signInProvider === 'anonymous';
        $isPhoneAuth = $signInProvider === 'phone' || !empty($phoneNumber);
        
        // Allow either phone authentication or anonymous authentication
        if (!$isPhoneAuth && !$isAnonymous) {
            return $this->unauthorizedResponse('Phone or anonymous authentication required.');
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
        
        // Set guest status based on anonymous authentication
        if ($isAnonymous) {
            $user->is_guest = true;
            // Generate a guest name if not provided
            if (empty($user->name) && empty($fbUser->displayName)) {
                $user->name = 'Guest '.substr($uid, -6);
            }
        } else {
            $user->is_guest = false;
        }
        
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
            if ($isAnonymous) {
                $user->name = 'Guest '.substr($uid, -6);
            } else {
                $user->name = 'User '.substr($uid, -6);
            }
        }
        if (empty($user->email)) {
            if ($isAnonymous) {
                $user->email = $uid.'@guest.firebase'; // placeholder unique email for guests
            } else {
                $user->email = $uid.'@phone.firebase'; // placeholder unique email for phone users
            }
        }
        // Do not require a local password for Firebase login; if column is non-nullable, set a random value that is never used
        if (empty($user->password)) {
            $user->password = Str::random(40);
        }

        $user->save();

        $status = $created ? 201 : 200;
        $message = $created ? 'User created' : 'Signed in';
        return response()->json([
            'success' => true,
            'message' => $message,
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
     * Verify phone number for an existing user (convert guest to verified user).
     * 
     * **Use Case:**
     * - User was previously authenticated anonymously (guest user)
     * - User completes phone verification in Firebase on client side
     * - Call this endpoint with the new phone-verified Firebase ID token in Authorization header
     * - User account is upgraded from guest to verified user
     * 
     * @OA\Post(
     *   path="/auth/firebase/verify-phone",
     *   tags={"Authentication"},
     *   security={{"firebaseAuth": {}}},
     *   summary="Verify phone number for existing user",
     *   description="Upgrades a guest user to a verified user by linking phone authentication. User must authenticate with their new phone-verified Firebase ID token in the Authorization header.",
     *   @OA\Response(
     *     response=200,
     *     description="Phone verification successful",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Phone verified successfully"),
     *       @OA\Property(property="data", ref="#/components/schemas/User")
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad Request – Phone verification required or user already verified",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized – Invalid/missing token",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   )
     * )
     */
    public function verifyPhoneNumber(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        
        // Get the Firebase ID token from the Authorization header
        $authHeader = $request->header('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Authorization header missing or invalid.');
        }
        
        $idToken = substr($authHeader, 7); // Remove 'Bearer ' prefix
        $auth = $this->firebaseAuth();

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

        
        // Verify the token belongs to the same user
        if ($currentUser->firebase_uid !== $uid) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Token does not match current user.');
        }

        try {
            $fbUser = $auth->getUser($uid);
        } catch (\Throwable $e) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Firebase user not found for provided token.');
        }

        // Verify this is phone authentication
        $signInProvider = $claims['firebase']['sign_in_provider'] ?? null;
        $phoneNumber = $fbUser->phoneNumber ?? null;
        $isPhoneAuth = $signInProvider === 'phone' || !empty($phoneNumber);
        
        if (!$isPhoneAuth) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Phone authentication required for verification.');
        }

        if (empty($phoneNumber)) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'Phone number not found in Firebase user.');
        }

        // Check if user is already verified
        if (!$currentUser->is_guest) {
            return $this->errorResponse(ErrorCode::INVALID_TOKEN, 'User is already verified.');
        }

        // Update user to verified status
        $currentUser->is_guest = false;
        $currentUser->phone_number = $phoneNumber;
        $currentUser->phone_verified_at = now();
        
        // Update other Firebase data if available
        if (!empty($fbUser->displayName)) {
            $currentUser->name = $fbUser->displayName;
        }
        if (!empty($fbUser->email)) {
            $currentUser->email = $fbUser->email;
            if ($fbUser->emailVerified) {
                $currentUser->email_verified_at = $currentUser->email_verified_at ?? now();
            }
        }

        $currentUser->save();

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully',
            'data' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
                'email_verified_at' => $currentUser->email_verified_at,
                'firebase_uid' => $currentUser->firebase_uid,
                'phone_number' => $currentUser->phone_number,
                'phone_verified_at' => $currentUser->phone_verified_at,
                'is_guest' => (bool) $currentUser->is_guest,
                'is_premium' => (bool) $currentUser->is_premium,
                'created_at' => $currentUser->created_at,
                'updated_at' => $currentUser->updated_at,
            ],
        ]);
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
     *   security={{"firebaseAuth": {}}},
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
