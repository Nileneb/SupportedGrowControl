<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevicePairingController extends Controller
{
    /**
     * Pair a device with the authenticated user.
     *
     * POST /api/devices/pair
     *
     * Request: { "bootstrap_code": "ABC123" }
     *
     * Response:
     * {
     *   "success": true,
     *   "device": {
     *     "id": 1,
     *     "name": "My Device",
     *     "public_id": "uuid",
     *     "paired_at": "2025-12-01 16:00:00"
     *   }
     * }
     */
    public function pair(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bootstrap_code' => 'required|string|size:6',
        ]);

        $device = Device::findByBootstrapCode($data['bootstrap_code']);

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid bootstrap code or device already paired.',
            ], 404);
        }

        // Pair device with authenticated user (returns plaintext token once!)
        $plaintextToken = $device->pairWithUser(Auth::id());

        Log::info('🎯 ENDPOINT_TRACKED: DevicePairingController@pair', [
            'user_id' => Auth::id(),
            'device_id' => $device->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device paired successfully!',
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'public_id' => $device->public_id,
                'paired_at' => $device->paired_at->toIso8601String(),
            ],
            'agent_token' => $plaintextToken, // Only returned once, never stored in plaintext!
        ]);
    }

    /**
     * List all unclaimed devices (for debugging/admin).
     *
     * GET /api/devices/unclaimed
     */
    public function unclaimed(): JsonResponse
    {
        $devices = Device::unclaimed()
            ->select(['id', 'name', 'bootstrap_id', 'bootstrap_code', 'created_at'])
            ->get();

        Log::info('🎯 ENDPOINT_TRACKED: DevicePairingController@unclaimed', [
            'device_count' => $devices->count(),
        ]);

        return response()->json([
            'devices' => $devices,
        ]);
    }
}
