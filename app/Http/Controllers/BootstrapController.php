<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BootstrapController extends Controller
{
    /**
     * Bootstrap endpoint for agents.
     *
     * POST /api/agents/bootstrap
     *
    * Request: { "name": "optional-device-name", "bootstrap_id": "optional-agent-id", "platform": "linux", "version": "2.0", "device_info": { ... } }
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
            'bootstrap_id' => 'nullable|string|max:64',
            'name' => 'nullable|string|max:255',
        ]);

        $bootstrapId = $data['bootstrap_id'];
        $device = Device::findByBootstrapId($bootstrapId);

        // Device doesn't exist yet - create unclaimed device
        if (! $device) {
            $device = Device::create([
                'bootstrap_id' => $bootstrapId,
                'name' => $data['name'] ?? 'Unclaimed Device',
                'slug' => 'device-' . Str::random(8),
            ]);

            return response()->json([
                'status' => 'unpaired',
                'bootstrap_id' => $device->bootstrap_id,
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

            Log::info('🎯 ENDPOINT_TRACKED: BootstrapController@bootstrap (paired)', [
                'bootstrap_id' => $bootstrapId,
                'device_id' => $device->id,
            ]);

            return response()->json([
                'status' => 'paired',
                'bootstrap_id' => $device->bootstrap_id,
                'device_id' => $device->public_id, // Agent expects device_id (which is our public_id UUID)
                'public_id' => $device->public_id,
                'device_token' => $plaintextToken, // New plaintext token (never stored!)
                'device_name' => $device->name,
                'user_email' => optional($device->user)->email,
            ]);
        }

        // Device exists but not yet paired
        Log::info('🎯 ENDPOINT_TRACKED: BootstrapController@bootstrap (pending)', [
            'bootstrap_id' => $bootstrapId,
            'device_id' => $device->id,
        ]);

        return response()->json([
            'status' => 'unpaired',
            'bootstrap_id' => $device->bootstrap_id,
            'bootstrap_code' => $device->bootstrap_code,
            'message' => "Device waiting for pairing. Use code: {$device->bootstrap_code}",
        ]);
    }

    /**
     * Pairing status polling endpoint for agents.
     *
    * GET /api/agents/pairing/status?bootstrap_id=xxx
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
            'bootstrap_code' => 'nullable|string|size:6',
        ]);

        $query = Device::where('bootstrap_id', $data['bootstrap_id']);
        if (!empty($data['bootstrap_code'])) {
            $query->where('bootstrap_code', $data['bootstrap_code']);
        }

        $device = $query->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid bootstrap_id or bootstrap_code',
            ], 404);
        }

        // Not yet paired
        if (!$device->isPaired()) {
            return response()->json([
                'status' => 'unpaired',
            ]);
        }

        // Paired! Return credentials (only once after pairing)
        // Generate new token for security
        $plaintextToken = Str::random(64);
        $device->agent_token = hash('sha256', $plaintextToken);
        $device->save();

        Log::info('🎯 ENDPOINT_TRACKED: BootstrapController@status (paired)', [
            'device_id' => $device->id,
        ]);

        return response()->json([
            'status' => 'paired',
            'bootstrap_id' => $device->bootstrap_id,
            'device_id' => $device->public_id, // Agent expects device_id (which is our public_id UUID)
            'public_id' => $device->public_id,
            'device_token' => $plaintextToken,
            'device_name' => $device->name,
            'user_email' => optional($device->user)->email,
        ]);
    }

    /**
     * User pairs device with bootstrap code.
     *
     * POST /api/devices/pair
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
                'message' => 'Invalid bootstrap code',
            ], 404);
        }

        if ($device->isPaired()) {
            return response()->json([
                'success' => false,
                'message' => 'Device already paired',
            ], 400);
        }

        // Pair device with authenticated user
        $plaintextToken = $device->pairWithUser($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Device paired successfully',
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'public_id' => $device->public_id,
                'paired_at' => $device->paired_at->toISOString(),
            ],
            'agent_token' => $plaintextToken,
        ]);
    }
}
