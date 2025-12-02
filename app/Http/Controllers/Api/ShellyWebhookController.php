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
     * Endpoint: POST /api/shelly/webhook/{public_id}
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
    public function handle(Request $request, string $publicId): JsonResponse
    {
        // Find device by public_id
        $device = Device::findByPublicId($publicId);

        if (!$device) {
            Log::warning('Shelly webhook: Device not found', [
                'public_id' => $publicId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Device not found',
            ], 404);
        }

        // Check if Shelly integration is configured
        if (!$device->hasShellyIntegration()) {
            Log::warning('Shelly webhook: Integration not configured', [
                'device_id' => $device->id,
                'public_id' => $publicId,
            ]);

            return response()->json([
                'error' => 'Shelly integration not configured for this device',
            ], 403);
        }

        // Verify authentication token from header or query parameter
        $token = $request->header('X-Shelly-Auth-Token') ?? $request->query('token');

        if (!$token || !$device->verifyShellyToken($token)) {
            Log::warning('Shelly webhook: Invalid authentication token', [
                'device_id' => $device->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Invalid authentication token',
            ], 401);
        }

        // Parse webhook payload
        $payload = $request->all();

        Log::info('Shelly webhook received', [
            'device_id' => $device->id,
            'shelly_device' => $payload['device'] ?? 'unknown',
            'event' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
        ]);

        // Store telemetry readings
        $this->storeTelemetryReadings($device, $payload);

        // Update device last_seen_at and record webhook timestamp
        $device->update(['last_seen_at' => now()]);
        $device->recordShellyWebhook();

        // Store raw webhook data in shelly_config for debugging
        $config = $device->shelly_config ?? [];
        $config['last_webhook'] = [
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ];
        $device->update(['shelly_config' => $config]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'device' => $device->public_id,
        ]);
    }

    /**
     * Store telemetry readings from Shelly webhook payload.
     */
    private function storeTelemetryReadings(Device $device, array $payload): void
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
