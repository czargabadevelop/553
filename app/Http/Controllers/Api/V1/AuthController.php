<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:64'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('username', $validated['username'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'The provided credentials do not match our records.',
            ]);
        }

        if ($user->isSuspended()) {
            return response()->json(['message' => 'This account is suspended.'], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($validated['device_name'] ?? 'api', ['read', 'play', 'wallet']);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user->loadMissing('roles', 'profile')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
