<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\TelemetryReading;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ShellyWebhookController extends Controller
{
    /**
     * Handle incoming Shelly webhook.
     * 
     * Endpoint: POST /api/shelly/webhook/{shelly_id}
     * 
     * Expected payload from Shelly device:
     * {
     *   "device": "shellyplug-s-XXXXX",
     *   "event": "switch/0",
     *   "state": "on|off",
     *   "power": 12.5,
     *   "voltage": 230.1,
     *   "current": 0.054,
     *   "temperature": 45.2,
     *   "overtemperature": false
     * }
     */
    public function handle(Request $request, int $shellyId): JsonResponse
    {
        // Find Shelly device by ID
        $shelly = \App\Models\ShellyDevice::find($shellyId);

        if (!$shelly) {
            Log::warning('Shelly webhook: Device not found', [
                'shelly_id' => $shellyId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Shelly device not found',
            ], 404);
        }

        // Verify authentication token from header or query parameter
        $token = $request->header('X-Shelly-Auth-Token') ?? $request->query('token');

        if (!$token || !$shelly->verifyToken($token)) {
            Log::warning('Shelly webhook: Invalid authentication token', [
                'shelly_id' => $shellyId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Invalid authentication token',
            ], 401);
        }

        // Parse webhook payload
        $payload = $request->all();

        Log::info('Shelly webhook received', [
            'shelly_id' => $shellyId,
            'shelly_device' => $payload['device'] ?? 'unknown',
            'event' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
        ]);

        // Store telemetry readings if device is linked
        if ($shelly->device_id) {
            $this->storeTelemetryReadings($shelly->device, $payload);
        }

        // Record webhook received
        $shelly->recordWebhook();

        // Store raw webhook data in config for debugging
        $config = $shelly->config ?? [];
        $config['last_webhook'] = [
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ];
        $shelly->update(['config' => $config]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'shelly_id' => $shellyId,
        ]);
    }

    /**
     * Store telemetry readings from Shelly webhook payload.
     */
    private function storeTelemetryReadings(\App\Models\Device $device, array $payload): void
    {
        $readings = [];

        // Map Shelly payload fields to telemetry readings
        $fieldMapping = [
            'power' => ['unit' => 'W', 'category' => 'power'],
            'voltage' => ['unit' => 'V', 'category' => 'power'],
            'current' => ['unit' => 'A', 'category' => 'power'],
            'temperature' => ['unit' => '°C', 'category' => 'environmental'],
            'energy' => ['unit' => 'Wh', 'category' => 'power'],
            'humidity' => ['unit' => '%', 'category' => 'environmental'],
            'illuminance' => ['unit' => 'lux', 'category' => 'environmental'],
        ];

        foreach ($fieldMapping as $field => $meta) {
            if (isset($payload[$field]) && is_numeric($payload[$field])) {
                $readings[] = [
                    'device_id' => $device->id,
                    'sensor_key' => "shelly_{$field}",
                    'value' => (float) $payload[$field],
                    'unit' => $meta['unit'],
                    'metadata' => [
                        'source' => 'shelly_webhook',
                        'shelly_device' => $payload['device'] ?? null,
                        'event' => $payload['event'] ?? null,
                    ],
                    'measured_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Store switch state as boolean telemetry
        if (isset($payload['state'])) {
            $readings[] = [
                'device_id' => $device->id,
                'sensor_key' => 'shelly_switch_state',
                'value' => $payload['state'] === 'on' ? 1.0 : 0.0,
                'unit' => 'bool',
                'metadata' => [
                    'source' => 'shelly_webhook',
                    'event' => $payload['event'] ?? null,
                    'state_string' => $payload['state'],
                ],
                'measured_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($readings)) {
            TelemetryReading::insert($readings);

            Log::info('Stored Shelly telemetry readings', [
                'device_id' => $device->id,
                'count' => count($readings),
            ]);
        }
    }
}
