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

class AuthMiddleware
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

        // Try Firebase authentication (supports both phone and anonymous auth)
        if ($this->tryFirebaseAuth($request, $authorization)) {
            return $next($request);
        }

        // Firebase authentication failed
        return $this->unauthorizedResponse('Invalid or expired Firebase token.');
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

            // Check if this is anonymous authentication
            $signInProvider = $claims['firebase']['sign_in_provider'] ?? null;
            $isAnonymous = $signInProvider === 'anonymous';

            $fbUser = $auth->getUser($uid);
            $user = User::where('firebase_uid', $uid)->first();
            
            if (!$user && !empty($fbUser->email)) {
                $user = User::where('email', $fbUser->email)->first();
            }
            
            if (!$user) {
                $user = new User();
            }

            $user->firebase_uid = $uid;
            
            // Set guest status based on authentication type
            if ($isAnonymous) {
                $user->is_guest = true;
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
            if (!empty($fbUser->phoneNumber)) {
                $user->phone_number = $fbUser->phoneNumber;
                $user->phone_verified_at = $user->phone_verified_at ?? now();
            }

            if (empty($user->name)) {
                if ($isAnonymous) {
                    $user->name = 'Guest '.substr($uid, -6);
                } else {
                    $user->name = 'User '.substr($uid, -6);
                }
            }
            if (empty($user->email)) {
                if ($isAnonymous) {
                    $user->email = $uid.'@guest.firebase';
                } else {
                    $user->email = $uid.'@phone.firebase';
                }
            }
            if (empty($user->password)) {
                $user->password = \Illuminate\Support\Str::random(40);
            }

            $user->save();

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);

            return true;
        } catch (RevokedIdToken|FailedToVerifyToken $e) {
            return false;
        } catch (\Throwable $e) {
            Log::warning('Firebase token verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
