<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeviceManagementController extends Controller
{
    /**
     * Update device capabilities (sensors/actuators)
     * POST /api/growdash/agent/capabilities
     *
     * Expected payload (complete schema):
     * {
     *   "capabilities": {
     *     "board": {
     *       "id": "arduino_uno",
     *       "vendor": "Arduino",
     *       "model": "UNO R3",
     *       "connection": "serial",
     *       "firmware": "growdash-unified-v1.0.0"
     *     },
     *     "sensors": [
     *       {
     *         "id": "water_level",
     *         "display_name": "Water Level",
     *         "category": "environment",
     *         "unit": "%",
     *         "value_type": "float",
     *         "range": [0, 100],
     *         "min_interval": 10,
     *         "critical": true
     *       }
     *     ],
     *     "actuators": [
     *       {
     *         "id": "spray_pump",
     *         "display_name": "Spray Pump",
     *         "category": "irrigation",
     *         "command_type": "duration",
     *         "params": [
     *           { "name": "seconds", "type": "int", "min": 1, "max": 120 }
     *         ],
     *         "min_interval": 30,
     *         "critical": true
     *       }
     *     ]
     *   }
     * }
     */

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
        $device = $request->attributes->get('device');

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

        Log::info('🎯 ENDPOINT_TRACKED: DeviceManagementController@heartbeat', [
            'device_id' => $device->id,
            'status' => 'online',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received',
        ], 200);
    }

}
