<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterFromInvitationRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterFromInvitationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken($validated['device_name'] ?? 'api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 422);
        }

        $token = $user->createToken($validated['device_name'] ?? 'api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken) {
            PersonalAccessToken::findToken($plainTextToken)?->delete();
        }

        return response()->json([], 204);
    }
}
