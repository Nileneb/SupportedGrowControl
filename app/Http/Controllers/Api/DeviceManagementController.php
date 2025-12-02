<?php

namespace App\Http\Controllers\Api;

use App\DTOs\DeviceCapabilities;
use App\Events\DeviceCapabilitiesUpdated;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SensorType;
use App\Models\ActuatorType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function updateCapabilities(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();

        // Detect and normalize capabilities format
        $capabilities = $request->input('capabilities', []);
        $capabilities = $this->normalizeCapabilities($capabilities);

        // Validate normalized format (relaxed validation)
        $validator = Validator::make(['capabilities' => $capabilities], [
            'capabilities' => 'required|array',
            'capabilities.board' => 'nullable',
            'capabilities.sensors' => 'nullable|array',
            'capabilities.actuators' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Store normalized capabilities (skip DTO validation, just store raw)
        $updateData = [
            'capabilities' => $capabilities,
        ];

        // Extract board ID/name and store in board_type column
        if (isset($capabilities['board']['id'])) {
            $updateData['board_type'] = $capabilities['board']['id'];
        } elseif (isset($capabilities['board_name'])) {
            $updateData['board_type'] = $capabilities['board_name'];
        }

        $device->update($updateData);

        // Broadcast WebSocket event
        broadcast(new DeviceCapabilitiesUpdated($device));

        // Count sensors and actuators
        $sensorCount = is_array($capabilities['sensors'] ?? null) ? count($capabilities['sensors']) : 0;
        $actuatorCount = is_array($capabilities['actuators'] ?? null) ? count($capabilities['actuators']) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Device capabilities updated',
            'board_type' => $device->board_type,
            'sensor_count' => $sensorCount,
            'actuator_count' => $actuatorCount,
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
        $device = $request->user();

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

    /**
     * Normalize capabilities from simplified agent format to full schema.
     * 
     * Accepts:
     * - Simple format: {"board_name": "arduino_uno", "sensors": ["water_level"], "actuators": ["spray_pump"]}
     * - Full format: {"board": {...}, "sensors": [{id, display_name, ...}], "actuators": [{id, display_name, ...}]}
     * 
     * Returns normalized full format (or passes through if already full).
     */
    private function normalizeCapabilities(array $capabilities): array
    {
        // Detect simple format (sensors/actuators as string arrays)
        $hasSensorsAsStrings = isset($capabilities['sensors']) 
            && is_array($capabilities['sensors'])
            && !empty($capabilities['sensors'])
            && is_string($capabilities['sensors'][0] ?? null);

        $hasActuatorsAsStrings = isset($capabilities['actuators'])
            && is_array($capabilities['actuators'])
            && !empty($capabilities['actuators'])
            && is_string($capabilities['actuators'][0] ?? null);

        // If already in full format, return as-is
        if (!$hasSensorsAsStrings && !$hasActuatorsAsStrings) {
            return $capabilities;
        }

        // Normalize to full format
        $normalized = [];

        // Board
        if (isset($capabilities['board'])) {
            $normalized['board'] = $capabilities['board'];
        } elseif (isset($capabilities['board_name'])) {
            $normalized['board'] = [
                'id' => $capabilities['board_name'],
                'vendor' => 'Unknown',
                'model' => ucfirst($capabilities['board_name']),
                'connection' => 'serial',
            ];
        }

        // Sensors
        if ($hasSensorsAsStrings) {
            $normalized['sensors'] = array_map(function ($sensorId) {
                return [
                    'id' => $sensorId,
                    'display_name' => ucwords(str_replace('_', ' ', $sensorId)),
                    'category' => $this->guessCategory($sensorId),
                    'unit' => $this->guessUnit($sensorId),
                    'value_type' => 'float',
                    'critical' => false,
                ];
            }, $capabilities['sensors']);
        } elseif (isset($capabilities['sensors'])) {
            $normalized['sensors'] = $capabilities['sensors'];
        }

        // Actuators
        if ($hasActuatorsAsStrings) {
            $normalized['actuators'] = array_map(function ($actuatorId) {
                return [
                    'id' => $actuatorId,
                    'display_name' => ucwords(str_replace('_', ' ', $actuatorId)),
                    'category' => $this->guessCategory($actuatorId),
                    'command_type' => $this->guessCommandType($actuatorId),
                    'critical' => false,
                ];
            }, $capabilities['actuators']);
        } elseif (isset($capabilities['actuators'])) {
            $normalized['actuators'] = $capabilities['actuators'];
        }

        return $normalized;
    }

    /**
     * Guess category from sensor/actuator ID.
     */
    private function guessCategory(string $id): string
    {
        $id = strtolower($id);

        if (str_contains($id, 'water') || str_contains($id, 'spray') || str_contains($id, 'pump') || str_contains($id, 'fill')) {
            return 'irrigation';
        }
        if (str_contains($id, 'tds') || str_contains($id, 'ph') || str_contains($id, 'ec')) {
            return 'nutrients';
        }
        if (str_contains($id, 'temp') || str_contains($id, 'humid')) {
            return 'environment';
        }
        if (str_contains($id, 'light') || str_contains($id, 'led')) {
            return 'lighting';
        }

        return 'custom';
    }

    /**
     * Guess unit from sensor ID.
     */
    private function guessUnit(string $id): string
    {
        $id = strtolower($id);

        if (str_contains($id, 'temp')) return '°C';
        if (str_contains($id, 'humid')) return '%';
        if (str_contains($id, 'water') || str_contains($id, 'level')) return '%';
        if (str_contains($id, 'tds')) return 'ppm';
        if (str_contains($id, 'ph')) return 'pH';
        if (str_contains($id, 'ec')) return 'mS/cm';

        return 'unit';
    }

    /**
     * Guess command type from actuator ID.
     */
    private function guessCommandType(string $id): string
    {
        $id = strtolower($id);

        if (str_contains($id, 'pump') || str_contains($id, 'spray')) return 'duration';
        if (str_contains($id, 'valve') || str_contains($id, 'fill')) return 'toggle';
        if (str_contains($id, 'light') || str_contains($id, 'led')) return 'toggle';

        return 'toggle';
    }
}
