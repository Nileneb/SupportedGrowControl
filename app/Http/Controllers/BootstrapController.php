<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            // IMPORTANT: Re-generate token for security (in case agent lost it)
            // Agent should store this token securely!
            $plaintextToken = Str::random(64);
            $device->agent_token = hash('sha256', $plaintextToken);
            $device->save();

            return response()->json([
                'status' => 'paired',
                'public_id' => $device->public_id,
                'agent_token' => $plaintextToken, // New plaintext token (never stored!)
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

    /**
     * Pairing status polling endpoint for agents.
     *
     * GET /api/agents/pairing/status?bootstrap_id=xxx&bootstrap_code=xxx
     *
     * Agent polls this endpoint to check if user has paired the device.
     *
     * Response (pending):
     * {
     *   "status": "pending"
     * }
     *
     * Response (paired):
     * {
     *   "status": "paired",
     *   "public_id": "uuid",
     *   "agent_token": "long-token",
     *   "device_name": "My Device",
     *   "user_email": "user@example.com"
     * }
     */
    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bootstrap_id' => 'required|string|max:64',
            'bootstrap_code' => 'required|string|size:6',
        ]);

        $device = Device::where('bootstrap_id', $data['bootstrap_id'])
            ->where('bootstrap_code', $data['bootstrap_code'])
            ->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid bootstrap_id or bootstrap_code',
            ], 404);
        }

        // Not yet paired
        if (!$device->isPaired()) {
            return response()->json([
                'status' => 'pending',
            ]);
        }

        // Paired! Return credentials (only once after pairing)
        // Generate new token for security
        $plaintextToken = Str::random(64);
        $device->agent_token = hash('sha256', $plaintextToken);
        $device->save();

        return response()->json([
            'status' => 'paired',
            'public_id' => $device->public_id,
            'agent_token' => $plaintextToken,
            'device_name' => $device->name,
            'user_email' => $device->user->email ?? null,
        ]);
    }
}
