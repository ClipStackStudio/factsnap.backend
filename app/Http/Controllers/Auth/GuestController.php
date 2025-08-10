<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestController extends Controller
{
    public function createGuest(Request $request)
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
        $token = $user->createToken('guest', $abilities)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_guest' => $user->is_guest,
                'is_premium' => $user->is_premium,
                'abilities' => $abilities,
            ]
        ], Response::HTTP_CREATED);
    }
}
