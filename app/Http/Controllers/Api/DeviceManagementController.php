<?php

namespace App\Http\Controllers\Api;

use App\Events\DeviceEventBroadcast;
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
            'logs' => 'nullable|array|max:100',
            'logs.*.level' => 'required_with:logs|in:debug,info,warning,error',
            'logs.*.message' => 'required_with:logs|string|max:5000',
            'logs.*.context' => 'nullable|array',
            'logs.*.timestamp' => 'nullable|string',
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

        // Process logs if included in heartbeat (Agent optimization)
        $logsProcessed = 0;
        if ($request->has('logs')) {
            $logs = $request->input('logs');
            
            foreach ($logs as $log) {
                $context = $log['context'] ?? [];
                
                $device->deviceLogs()->create([
                    'level' => $log['level'],
                    'message' => $log['message'],
                    'context' => !empty($context) ? $context : null,
                    'agent_timestamp' => $log['timestamp'] ?? null,
                ]);
                
                // Broadcast log to WebSocket (Real-time Serial Console); do not fail heartbeat if broadcast backend is down
                try {
                    broadcast(new DeviceEventBroadcast(
                        $device,
                        'log.received',
                        [
                            'level' => $log['level'],
                            'message' => $log['message'],
                            'agent_timestamp' => $log['timestamp'] ?? null,
                        ]
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast failed for device log', [
                        'device_id' => $device->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                $logsProcessed++;
            }
        }

        Log::info('🎯 ENDPOINT_TRACKED: DeviceManagementController@heartbeat', [
            'device_id' => $device->id,
            'status' => 'online',
            'logs_processed' => $logsProcessed,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received',
        ], 200);
    }

}
