<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Issue a Sanctum token for API/Agent usage.
     * POST /api/auth/login
     * Body: { email, password }
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'token_name' => 'nullable|string|max:64',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $tokenName = $data['token_name'] ?? 'agent-registration';
        $plainToken = $user->createToken($tokenName)->plainTextToken;

        Log::info('🎯 ENDPOINT_TRACKED: AuthController@login', [
            'user_id' => $user->id,
            'token_name' => $tokenName,
        ]);

        return response()->json([
            'success' => true,
            'token' => $plainToken,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * Revoke the current Sanctum token (logout).
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $request->user()->currentAccessToken()->delete();

        Log::info('🎯 ENDPOINT_TRACKED: AuthController@logout', [
            'user_id' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
