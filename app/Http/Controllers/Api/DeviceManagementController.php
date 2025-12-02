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
     *     "board_name": "arduino_uno",
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
            'capabilities.board_name' => 'nullable|string|max:50',
            'capabilities.sensors' => 'nullable|array',
            'capabilities.actuators' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [
            'capabilities' => $request->input('capabilities'),
        ];

        // Extract board_name from capabilities and store in dedicated column
        if ($request->input('capabilities.board_name')) {
            $updateData['board_type'] = $request->input('capabilities.board_name');
        }

        $device->update($updateData);

        // Broadcast WebSocket event
        broadcast(new DeviceCapabilitiesUpdated($device));

        return response()->json([
            'success' => true,
            'message' => 'Device capabilities updated',
            'board_type' => $device->board_type,
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
