<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Agent API Controller
 *
 * Handles all communication from agent to Laravel backend.
 * All routes protected by device.auth middleware.
 *
 * Base URL: /api/growdash/agent
 */
class AgentController extends Controller
{
    /**
     * POST /api/growdash/agent/heartbeat
     *
     * Agent sends heartbeat every 30 seconds to keep device online status.
     *
     * Request:
     * {
     *   "ip_address": "192.168.1.100",
     *   "api_port": 8000
     * }
     *
     * Response:
     * {
     *   "success": true
     * }
     */
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'ip_address' => 'nullable|ip',
            'api_port' => 'nullable|integer|between:1,65535',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update device status and last_seen_at
        $updateData = [
            'last_seen_at' => now(),
            'status' => 'online',
        ];

        // Optionally update IP and API port if provided
        if ($request->has('ip_address')) {
            $updateData['ip_address'] = $request->input('ip_address');
        }
        if ($request->has('api_port')) {
            $updateData['api_port'] = $request->input('api_port');
        }

        $device->update($updateData);

        Log::info('Agent heartbeat received', [
            'device_id' => $device->id,
            'ip_address' => $request->input('ip_address'),
            'api_port' => $request->input('api_port'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/growdash/agent/telemetry
     *
     * Agent sends telemetry data from Arduino (sensor readings).
     *
     * Request:
     * {
     *   "telemetry": [
     *     {
     *       "type": "WaterLevel",
     *       "value": "45",
     *       "timestamp": "2025-12-05T10:30:00Z"
     *     },
     *     {
     *       "type": "Temperature",
     *       "value": "22.5",
     *       "timestamp": "2025-12-05T10:30:01Z"
     *     }
     *   ]
     * }
     *
     * Response:
     * {
     *   "success": true
     * }
     */
    public function telemetry(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'telemetry' => 'required|array|min:1|max:100',
            'telemetry.*.type' => 'required|string|max:50',
            'telemetry.*.value' => 'required|string|max:255',
            'telemetry.*.timestamp' => 'required|date_format:Y-m-d\TH:i:s\Z',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $telemetryItems = $request->input('telemetry', []);
        $inserted = [];

        foreach ($telemetryItems as $item) {
            try {
                $telemetry = $device->telemetry()->create([
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'timestamp' => $item['timestamp'],
                ]);

                $inserted[] = [
                    'id' => $telemetry->id,
                    'type' => $telemetry->type,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to store telemetry', [
                    'device_id' => $device->id,
                    'item' => $item,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Telemetry received', [
            'device_id' => $device->id,
            'count' => count($inserted),
        ]);

        return response()->json([
            'success' => true,
            'inserted' => count($inserted),
        ]);
    }

    /**
     * GET /api/growdash/agent/commands/pending
     *
     * Agent polls for pending commands to execute.
     *
     * Response:
     * {
     *   "success": true,
     *   "commands": [
     *     {
     *       "id": "cmd-123",
     *       "type": "serial_command",
     *       "params": {
     *         "command": "Status"
     *       }
     *     },
     *     {
     *       "id": "cmd-124",
     *       "type": "arduino_upload",
     *       "params": {
     *         "code": "void setup() {...}",
     *         "board": "arduino:avr:uno",
     *         "port": "/dev/ttyACM0"
     *       }
     *     }
     *   ]
     * }
     */
    public function pendingCommands(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $commands = $device->commands()
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'type', 'params']);

        return response()->json([
            'success' => true,
            'commands' => $commands,
        ]);
    }

    /**
     * POST /api/growdash/agent/commands/{id}/result
     *
     * Agent reports command execution result back to Laravel.
     *
     * Request (Success):
     * {
     *   "status": "completed",
     *   "result_message": "✅ Upload auf /dev/ttyACM0",
     *   "output": "Sketch uses 1234 bytes...",
     *   "error": ""
     * }
     *
     * Request (Failed):
     * {
     *   "status": "failed",
     *   "result_message": "❌ Compile-Fehler",
     *   "output": "Linking everything together...",
     *   "error": "error: 'LO' was not declared in this scope"
     * }
     *
     * Response:
     * {
     *   "success": true
     * }
     */
    public function commandResult(Request $request, int $id): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $command = $device->commands()
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,failed',
            'result_message' => 'nullable|string|max:1000',
            'output' => 'nullable|string',
            'error' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Build result_data JSON from request
        $resultData = [];
        if ($request->has('output')) {
            $resultData['output'] = $request->input('output');
        }
        if ($request->has('error')) {
            $resultData['error'] = $request->input('error');
        }

        $command->update([
            'status' => $request->input('status'),
            'result_message' => $request->input('result_message'),
            'result_data' => !empty($resultData) ? $resultData : null,
            'completed_at' => now(),
        ]);

        Log::info('Command result received', [
            'command_id' => $command->id,
            'device_id' => $device->id,
            'status' => $request->input('status'),
            'message' => $request->input('result_message'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/growdash/agent/capabilities
     *
     * Agent reports device capabilities (sensors, actuators, board info).
     *
     * Request:
     * {
     *   "board": {
     *     "name": "Arduino Uno",
     *     "type": "arduino:avr:uno",
     *     "firmware": "GrowDash v1.0"
     *   },
     *   "sensors": [
     *     {
     *       "id": "water_level",
     *       "name": "Water Level",
     *       "unit": "%",
     *       "min": 0,
     *       "max": 100
     *     }
     *   ],
     *   "actuators": [
     *     {
     *       "id": "spray_pump",
     *       "name": "Spray Pump",
     *       "type": "relay"
     *     }
     *   ]
     * }
     *
     * Response:
     * {
     *   "success": true
     * }
     */
    public function updateCapabilities(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'board' => 'nullable|array',
            'sensors' => 'nullable|array',
            'actuators' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $capabilities = [];
        if ($request->has('board')) {
            $capabilities['board'] = $request->input('board');
        }
        if ($request->has('sensors')) {
            $capabilities['sensors'] = $request->input('sensors');
        }
        if ($request->has('actuators')) {
            $capabilities['actuators'] = $request->input('actuators');
        }

        $device->update([
            'capabilities' => $capabilities,
        ]);

        Log::info('Device capabilities updated', [
            'device_id' => $device->id,
            'sensors' => count($request->input('sensors', [])),
            'actuators' => count($request->input('actuators', [])),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/growdash/agent/capabilities
     *
     * Agent retrieves stored device capabilities.
     *
     * Response:
     * {
     *   "success": true,
     *   "capabilities": { ... }
     * }
     */
    public function getCapabilities(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        return response()->json([
            'success' => true,
            'capabilities' => $device->capabilities ?? [],
        ]);
    }

    /**
     * POST /api/growdash/agent/logs
     *
     * Agent sends device logs to Laravel backend.
     *
     * Request:
     * {
     *   "logs": [
     *     {
     *       "level": "info",
     *       "message": "Device initialized",
     *       "timestamp": "2025-12-05T10:30:00Z"
     *     }
     *   ]
     * }
     *
     * Response:
     * {
     *   "success": true
     * }
     */
    public function storeLogs(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'logs' => 'required|array|min:1|max:100',
            'logs.*.level' => 'required|in:debug,info,warning,error',
            'logs.*.message' => 'required|string|max:1000',
            'logs.*.timestamp' => 'nullable|date_format:Y-m-d\TH:i:s\Z',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $logs = $request->input('logs', []);
        $inserted = [];

        foreach ($logs as $logItem) {
            try {
                $log = $device->logs()->create([
                    'level' => $logItem['level'],
                    'message' => $logItem['message'],
                    'timestamp' => $logItem['timestamp'] ?? now(),
                ]);

                $inserted[] = $log->id;
            } catch (\Exception $e) {
                Log::error('Failed to store device log', [
                    'device_id' => $device->id,
                    'log' => $logItem,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Device logs received', [
            'device_id' => $device->id,
            'count' => count($inserted),
        ]);

        return response()->json([
            'success' => true,
            'inserted' => count($inserted),
        ]);
    }

    /**
     * GET /api/growdash/agent/ports
     *
     * Returns available serial ports on the agent.
     * This is a fallback response - typically agent should proxy to its local /ports endpoint.
     *
     * Response:
     * {
     *   "success": true,
     *   "ports": [
     *     {
     *       "port": "/dev/ttyACM0",
     *       "description": "Arduino Uno",
     *       "vendor_id": "2341",
     *       "product_id": "0043"
     *     }
     *   ]
     * }
     */
    public function getPorts(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        // If device has IP address, proxy to agent's local /ports endpoint
        if ($device->ip_address) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->get("http://{$device->ip_address}:8000/ports");

                if ($response->successful()) {
                    return response()->json($response->json());
                }
            } catch (\Exception $e) {
                Log::warning('Failed to proxy ports request to agent', [
                    'device_id' => $device->id,
                    'ip_address' => $device->ip_address,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: Return common serial ports for manual selection
        $fallbackPorts = [
            // Linux
            ['port' => '/dev/ttyACM0', 'description' => 'Arduino (ACM)', 'vendor_id' => '', 'product_id' => ''],
            ['port' => '/dev/ttyACM1', 'description' => 'Arduino (ACM)', 'vendor_id' => '', 'product_id' => ''],
            ['port' => '/dev/ttyUSB0', 'description' => 'Serial Device (USB)', 'vendor_id' => '', 'product_id' => ''],
            ['port' => '/dev/ttyUSB1', 'description' => 'Serial Device (USB)', 'vendor_id' => '', 'product_id' => ''],
            // Windows
            ['port' => 'COM3', 'description' => 'Serial Port', 'vendor_id' => '', 'product_id' => ''],
            ['port' => 'COM4', 'description' => 'Serial Port', 'vendor_id' => '', 'product_id' => ''],
            ['port' => 'COM5', 'description' => 'Serial Port', 'vendor_id' => '', 'product_id' => ''],
        ];

        Log::info('Returning fallback ports', [
            'device_id' => $device->id,
            'reason' => $device->ip_address ? 'agent_unreachable' : 'no_ip_address',
        ]);

        return response()->json([
            'success' => true,
            'ports' => $fallbackPorts,
        ]);
    }
}
