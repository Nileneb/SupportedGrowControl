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

        $validator = Validator::make($request->all(), [
            'capabilities' => 'required|array',

            // Board validation
            'capabilities.board' => 'nullable|array',
            'capabilities.board.id' => 'required_with:capabilities.board|string|max:50',
            'capabilities.board.vendor' => 'nullable|string|max:100',
            'capabilities.board.model' => 'nullable|string|max:100',
            'capabilities.board.connection' => 'nullable|string|in:serial,wifi,ethernet,bluetooth',
            'capabilities.board.firmware' => 'nullable|string|max:100',

            // Sensors validation
            'capabilities.sensors' => 'nullable|array',
            'capabilities.sensors.*.id' => 'required|string|max:50',
            'capabilities.sensors.*.display_name' => 'required|string|max:100',
            'capabilities.sensors.*.category' => 'required|string|in:environment,nutrients,lighting,irrigation,system,custom',
            'capabilities.sensors.*.unit' => 'required|string|max:20',
            'capabilities.sensors.*.value_type' => 'required|string|in:float,int,string,bool',
            'capabilities.sensors.*.range' => 'nullable|array|size:2',
            'capabilities.sensors.*.range.*' => 'nullable|numeric',
            'capabilities.sensors.*.min_interval' => 'nullable|integer|min:1',
            'capabilities.sensors.*.critical' => 'boolean',

            // Actuators validation
            'capabilities.actuators' => 'nullable|array',
            'capabilities.actuators.*.id' => 'required|string|max:50',
            'capabilities.actuators.*.display_name' => 'required|string|max:100',
            'capabilities.actuators.*.category' => 'required|string|in:environment,nutrients,lighting,irrigation,system,custom',
            'capabilities.actuators.*.command_type' => 'required|string|in:toggle,duration,target,custom',
            'capabilities.actuators.*.params' => 'nullable|array',
            'capabilities.actuators.*.params.*.name' => 'required|string|max:50',
            'capabilities.actuators.*.params.*.type' => 'required|string|in:int,float,string,bool',
            'capabilities.actuators.*.params.*.min' => 'nullable|numeric',
            'capabilities.actuators.*.params.*.max' => 'nullable|numeric',
            'capabilities.actuators.*.params.*.unit' => 'nullable|string|max:20',
            'capabilities.actuators.*.min_interval' => 'nullable|integer|min:1',
            'capabilities.actuators.*.critical' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create DTO to validate structure
        try {
            $capabilitiesDTO = DeviceCapabilities::fromArray($request->input('capabilities'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid capabilities structure',
                'message' => $e->getMessage(),
            ], 422);
        }

        $updateData = [
            'capabilities' => $request->input('capabilities'),
        ];

        // Extract board ID and store in board_type column
        if (isset($request->input('capabilities')['board']['id'])) {
            $updateData['board_type'] = $request->input('capabilities')['board']['id'];
        }

        $device->update($updateData);

        // Broadcast WebSocket event
        broadcast(new DeviceCapabilitiesUpdated($device));

        // Classify capabilities against canonical catalog
        $sensorIds = array_map(fn($s) => $s->id, $capabilitiesDTO->sensors);
        $actuatorIds = array_map(fn($a) => $a->id, $capabilitiesDTO->actuators);

        $canonicalSensorIds = SensorType::query()->whereIn('id', $sensorIds)->pluck('id')->all();
        $canonicalActuatorIds = ActuatorType::query()->whereIn('id', $actuatorIds)->pluck('id')->all();

        $customSensorIds = array_values(array_diff($sensorIds, $canonicalSensorIds));
        $customActuatorIds = array_values(array_diff($actuatorIds, $canonicalActuatorIds));

        // Identify simple mismatches against catalog (non-fatal)
        $sensorMismatches = [];
        foreach ($capabilitiesDTO->sensors as $s) {
            $type = SensorType::find($s->id);
            if ($type) {
                $mismatch = [];
                if (!empty($s->unit) && $type->default_unit && $s->unit !== $type->default_unit) {
                    $mismatch[] = 'unit';
                }
                if (!empty($s->value_type) && $s->value_type !== $type->value_type) {
                    $mismatch[] = 'value_type';
                }
                if (!empty($mismatch)) {
                    $sensorMismatches[$s->id] = $mismatch;
                }
            }
        }

        $actuatorMismatches = [];
        foreach ($capabilitiesDTO->actuators as $a) {
            $type = ActuatorType::find($a->id);
            if ($type) {
                $mismatch = [];
                if (!empty($a->command_type) && $a->command_type !== $type->command_type) {
                    $mismatch[] = 'command_type';
                }
                if (!empty($mismatch)) {
                    $actuatorMismatches[$a->id] = $mismatch;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Device capabilities updated',
            'board_type' => $device->board_type,
            'capabilities' => $device->capabilities,
            'sensor_count' => count($capabilitiesDTO->sensors),
            'actuator_count' => count($capabilitiesDTO->actuators),
            'categories' => $capabilitiesDTO->getAllCategories(),
            'catalog' => [
                'sensors' => [
                    'canonical' => $canonicalSensorIds,
                    'custom' => $customSensorIds,
                    'mismatches' => $sensorMismatches,
                ],
                'actuators' => [
                    'canonical' => $canonicalActuatorIds,
                    'custom' => $customActuatorIds,
                    'mismatches' => $actuatorMismatches,
                ],
            ],
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
}
