<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Factory;

class FirebaseAuthenticate
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

        $auth = $this->firebaseAuth();

        try {
            $verifiedToken = $auth->verifyIdToken($authorization, true);
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
            $user->password = Str::random(40);
        }

        $user->save();

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
