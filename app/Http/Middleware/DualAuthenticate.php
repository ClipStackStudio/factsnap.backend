<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Factory;

class DualAuthenticate
{
    use \App\Http\Controllers\ApiErrorResponses;

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

    public function handle(Request $request, Closure $next): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $authorization = $request->bearerToken();
        if (!$authorization) {
            return $this->unauthorizedResponse('Missing Bearer token.');
        }

        // First, try Firebase authentication
        if ($this->tryFirebaseAuth($request, $authorization)) {
            return $next($request);
        }

        // If Firebase fails, try Sanctum authentication
        if ($this->trySanctumAuth($request)) {
            return $next($request);
        }

        // Both authentication methods failed
        return $this->unauthorizedResponse('Invalid or expired token.');
    }

    private function tryFirebaseAuth(Request $request, string $authorization): bool
    {
        try {
            $auth = $this->firebaseAuth();
            $verifiedToken = $auth->verifyIdToken($authorization, true);
            
            $claims = $verifiedToken->claims()->all();
            $uid = (string)($claims['sub'] ?? $claims['user_id'] ?? '');
            if ($uid === '') {
                return false;
            }

            $fbUser = $auth->getUser($uid);
            $user = User::where('firebase_uid', $uid)->first();
            
            if (!$user && !empty($fbUser->email)) {
                $user = User::where('email', $fbUser->email)->first();
            }
            
            if (!$user) {
                $user = new User();
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
            if (!empty($fbUser->phoneNumber)) {
                $user->phone_number = $fbUser->phoneNumber;
                $user->phone_verified_at = $user->phone_verified_at ?? now();
            }

            if (empty($user->name)) {
                $user->name = 'User '.substr($uid, -6);
            }
            if (empty($user->email)) {
                $user->email = $uid.'@phone.firebase';
            }
            if (empty($user->password)) {
                $user->password = \Illuminate\Support\Str::random(40);
            }

            $user->save();

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);

            return true;
        } catch (RevokedIdToken|FailedToVerifyToken $e) {
            // Firebase token is invalid, try Sanctum
            return false;
        } catch (\Throwable $e) {
            Log::warning('Firebase token verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function trySanctumAuth(Request $request): bool
    {
        try {
            // Use Sanctum's authentication logic
            $guard = Auth::guard('sanctum');
            $user = $guard->user();
            
            if ($user) {
                Auth::setUser($user);
                $request->setUserResolver(fn () => $user);
                return true;
            }
            
            return false;
        } catch (\Throwable $e) {
            Log::warning('Sanctum token verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
