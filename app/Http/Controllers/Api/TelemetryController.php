<?php

namespace App\Http\Controllers\Api;

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
        $device = $request->user('device'); // Set by AuthenticateDevice middleware

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

        $readings = $request->input('readings');
        $inserted = [];

        foreach ($readings as $reading) {
            $telemetry = $device->telemetryReadings()->create([
                'sensor_key' => $reading['sensor_key'],
                'value' => $reading['value'],
                'unit' => $reading['unit'] ?? null,
                'measured_at' => $reading['measured_at'],
                'raw' => $reading['raw'] ?? null,
            ]);

            $inserted[] = $telemetry->id;
        }

        // Broadcast WebSocket event
        broadcast(new DeviceTelemetryReceived($device, $readings));

        return response()->json([
            'success' => true,
            'message' => 'Telemetry data stored successfully',
            'inserted_count' => count($inserted),
            'ids' => $inserted,
        ], 201);
    }
}
