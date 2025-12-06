<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DevicePairing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GrowdashPairingController extends Controller
{
    /**
     * Agent-init: store pending pairing with expiry.
     */
    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:255',
            'pairing_code' => 'required|string|size:6',
            'device_info' => 'nullable|array',
        ]);

        $expiresAt = now()->addMinutes(5);

        $pairing = DevicePairing::updateOrCreate(
            ['device_id' => $validated['device_id']],
            [
                'pairing_code' => $validated['pairing_code'],
                'device_info' => $validated['device_info'] ?? [],
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'agent_token' => null,
                'user_id' => null,
            ],
        );

        Log::info('🎯 ENDPOINT_TRACKED: GrowdashPairingController@init', [
            'device_id' => $pairing->device_id,
            'pairing_code' => $pairing->pairing_code,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'status' => 'pending',
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    /**
     * Agent polls for pairing status.
     */
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:255',
            'pairing_code' => 'required|string|size:6',
        ]);

        $pairing = DevicePairing::where('device_id', $validated['device_id'])
            ->where('pairing_code', $validated['pairing_code'])
            ->first();

        if (!$pairing) {
            return response()->json(['status' => 'expired']);
        }

        if ($pairing->status !== 'paired' && $pairing->expires_at && $pairing->expires_at->isPast()) {
            $pairing->update(['status' => 'expired']);
            return response()->json(['status' => 'expired']);
        }

        if ($pairing->status === 'paired') {
            return response()->json([
                'status' => 'paired',
                'agent_token' => $pairing->agent_token,
                'user_email' => optional($pairing->user)->email,
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * User enters pairing code in frontend to claim device.
     */
    public function pair(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'pairing_code' => 'required|string|size:6',
        ]);

        $pairing = DevicePairing::where('pairing_code', $validated['pairing_code'])->first();

        if (!$pairing) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired pairing code.',
            ], 404);
        }

        if ($pairing->status !== 'paired' && $pairing->expires_at && $pairing->expires_at->isPast()) {
            $pairing->update(['status' => 'expired']);
            return response()->json([
                'success' => false,
                'status' => 'expired',
                'message' => 'Pairing code expired.',
            ], 400);
        }

        if ($pairing->status === 'paired') {
            return response()->json([
                'success' => true,
                'status' => 'paired',
                'device_id' => $pairing->device_id,
                'message' => 'Device already paired.',
            ], 200);
        }

        $agentTokenPlain = Str::random(64);
        $deviceName = $pairing->device_info['name'] ?? 'GrowDash Device';
        $slugBase = Str::slug($pairing->device_id ?: $deviceName) ?: 'growdash-device';
        $slug = $slugBase . '-' . Str::random(6);

        $device = Device::create([
            'user_id' => $user->id,
            'public_id' => (string) Str::uuid(),
            'name' => $deviceName,
            'slug' => $slug,
            'bootstrap_id' => $pairing->device_id,
            'agent_token' => hash('sha256', $agentTokenPlain),
            'device_info' => $pairing->device_info ?? [],
            'status' => 'paired',
            'paired_at' => now(),
        ]);

        $pairing->update([
            'status' => 'paired',
            'agent_token' => $agentTokenPlain,
            'user_id' => $user->id,
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: GrowdashPairingController@pair', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'pairing_id' => $pairing->id,
        ]);

        return response()->json([
            'success' => true,
            'device_id' => $pairing->device_id,
            'device_name' => $device->name,
        ]);
    }
}
