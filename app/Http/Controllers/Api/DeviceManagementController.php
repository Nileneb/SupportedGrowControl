<?php

namespace App\Http\Controllers\Api;

use App\Events\DeviceCapabilitiesUpdated;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceManagementController extends Controller
{
    /**
     * Update device capabilities (sensors/actuators)
     * POST /api/growdash/agent/capabilities
     *
     * Expected payload:
     * {
     *   "capabilities": {
     *     "sensors": ["water_level", "tds", "temperature"],
     *     "actuators": ["spray_pump", "fill_valve"]
     *   }
     * }
     */
    public function updateCapabilities(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user('device');

        $validator = Validator::make($request->all(), [
            'capabilities' => 'required|array',
            'capabilities.sensors' => 'nullable|array',
            'capabilities.actuators' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $device->update([
            'capabilities' => $request->input('capabilities'),
        ]);

        // Broadcast WebSocket event
        broadcast(new DeviceCapabilitiesUpdated($device));

        return response()->json([
            'success' => true,
            'message' => 'Device capabilities updated',
            'capabilities' => $device->capabilities,
        ]);
    }

    /**
     * Update device last_seen timestamp (heartbeat)
     * POST /api/growdash/agent/heartbeat
     *
     * Optional payload:
     * {
     *   "last_state": {
     *     "uptime": 3600,
     *     "memory": 45000,
     *     "wifi_rssi": -65
     *   }
     * }
     */
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user('device');

        $validator = Validator::make($request->all(), [
            'last_state' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [
            'last_seen_at' => now(),
            'status' => 'online', // Set status to online on heartbeat
        ];

        if ($request->has('last_state')) {
            $updateData['last_state'] = $request->input('last_state');
        }

        $device->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received',
            'last_seen_at' => $device->last_seen_at,
        ]);
    }
}
