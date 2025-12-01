<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    /**
     * Bootstrap endpoint for agents.
     * 
     * POST /api/agents/bootstrap
     * 
     * Request: { "bootstrap_id": "agent-unique-id", "name": "optional-device-name" }
     * 
     * Response (unpaired):
     * {
     *   "status": "unpaired",
     *   "bootstrap_code": "ABC123",
     *   "message": "Device registered. Please pair via web UI with code: ABC123"
     * }
     * 
     * Response (paired):
     * {
     *   "status": "paired",
     *   "public_id": "uuid",
     *   "device_token": "long-token",
     *   "device_name": "My Device"
     * }
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bootstrap_id' => 'required|string|max:64',
            'name' => 'nullable|string|max:255',
        ]);

        $bootstrapId = $data['bootstrap_id'];
        $device = Device::findByBootstrapId($bootstrapId);

        // Device doesn't exist yet - create unclaimed device
        if (!$device) {
            $device = Device::create([
                'bootstrap_id' => $bootstrapId,
                'name' => $data['name'] ?? 'Unclaimed Device',
                'slug' => 'device-' . substr($bootstrapId, 0, 8),
            ]);

            return response()->json([
                'status' => 'unpaired',
                'bootstrap_code' => $device->bootstrap_code,
                'message' => "Device registered. Please pair via web UI with code: {$device->bootstrap_code}",
            ], 201);
        }

        // Device exists and is paired
        if ($device->isPaired()) {
            return response()->json([
                'status' => 'paired',
                'public_id' => $device->public_id,
                'device_token' => $device->agent_token,
                'device_name' => $device->name,
                'user_email' => $device->user->email ?? null,
            ]);
        }

        // Device exists but not yet paired
        return response()->json([
            'status' => 'unpaired',
            'bootstrap_code' => $device->bootstrap_code,
            'message' => "Device waiting for pairing. Use code: {$device->bootstrap_code}",
        ]);
    }
}
