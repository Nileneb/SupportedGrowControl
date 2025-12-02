<?php

namespace App\Http\Controllers\Api;

use App\DTOs\DeviceCapabilities;
use App\Events\DeviceTelemetryReceived;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TelemetryController extends Controller
{
    /**
     * Store telemetry data from agent
     * POST /api/growdash/agent/telemetry
     *
     * Expected payload:
     * {
     *   "readings": [
     *     {
     *       "sensor_key": "water_level",
     *       "value": 75.5,
     *       "unit": "%",
     *       "measured_at": "2025-12-01T18:00:00Z",
     *       "raw": null
     *     },
     *     {
     *       "sensor_key": "tds",
     *       "value": 850,
     *       "unit": "ppm",
     *       "measured_at": "2025-12-01T18:00:00Z"
     *     }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user(); // Set by AuthenticateDevice middleware

        $validator = Validator::make($request->all(), [
            'readings' => 'required|array|min:1|max:100',
            'readings.*.sensor_key' => 'required|string|max:50',
            'readings.*.value' => 'required|numeric',
            'readings.*.unit' => 'nullable|string|max:20',
            'readings.*.measured_at' => 'required|date',
            'readings.*.raw' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Load device capabilities for validation
        $capabilitiesDTO = null;
        if ($device->capabilities) {
            try {
                $capabilitiesDTO = DeviceCapabilities::fromArray($device->capabilities);
            } catch (\Exception $e) {
                // Continue without validation if capabilities malformed
            }
        }

        $readings = $request->input('readings');
        $inserted = [];
        $skipped = [];
        $lastStateUpdates = [];

        foreach ($readings as $reading) {
            $sensorKey = $reading['sensor_key'];

            // Validate sensor exists in capabilities (if available)
            if ($capabilitiesDTO) {
                $sensor = $capabilitiesDTO->getSensorById($sensorKey);

                if (!$sensor) {
                    $skipped[] = [
                        'sensor_key' => $sensorKey,
                        'reason' => 'Sensor not found in device capabilities',
                    ];
                    continue;
                }

                // Validate value against sensor spec
                if (!$sensor->validateValue($reading['value'])) {
                    $skipped[] = [
                        'sensor_key' => $sensorKey,
                        'reason' => 'Value out of range or invalid type',
                    ];
                    continue;
                }

                // Validate unit matches
                if (isset($reading['unit']) && $reading['unit'] !== $sensor->unit) {
                    $skipped[] = [
                        'sensor_key' => $sensorKey,
                        'reason' => "Unit mismatch (expected: {$sensor->unit}, got: {$reading['unit']})",
                    ];
                    continue;
                }
            }

            // Store telemetry
            $telemetry = $device->telemetryReadings()->create([
                'sensor_key' => $reading['sensor_key'],
                'value' => $reading['value'],
                'unit' => $reading['unit'] ?? null,
                'measured_at' => $reading['measured_at'],
                'raw' => $reading['raw'] ?? null,
            ]);

            $inserted[] = $telemetry->id;

            // Update last_state cache
            $lastStateUpdates[$sensorKey] = [
                'value' => $reading['value'],
                'unit' => $reading['unit'] ?? null,
                'timestamp' => $reading['measured_at'],
            ];
        }

        // Update device last_state JSON with latest sensor values
        if (!empty($lastStateUpdates)) {
            $currentLastState = $device->last_state ?? [];
            $device->update([
                'last_state' => array_merge($currentLastState, $lastStateUpdates),
            ]);
        }

        // Broadcast WebSocket event
        broadcast(new DeviceTelemetryReceived($device, $readings));

        return response()->json([
            'success' => true,
            'message' => 'Telemetry data stored successfully',
            'inserted_count' => count($inserted),
            'skipped_count' => count($skipped),
            'ids' => $inserted,
            'skipped' => $skipped,
        ], 201);
    }
}
