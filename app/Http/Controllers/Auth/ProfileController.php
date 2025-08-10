<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'firebase_uid' => $user->firebase_uid,
            'is_premium' => $user->is_premium,
            'abilities' => $request->user()->currentAccessToken()->abilities ?? [],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
