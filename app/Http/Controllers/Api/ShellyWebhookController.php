<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
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
    use Illuminate\Support\Facades\Log;
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

        // Telemetry storage removed (telemetry disabled)

        // Record webhook received
        $shelly->recordWebhook();

        // Store raw webhook data in config for debugging
        $config = $shelly->config ?? [];
        $config['last_webhook'] = [
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ]);
        $shelly->update(['config' => $config]);

        Log::info('🎯 ENDPOINT_TRACKED: ShellyWebhookController@handle', [
            'shelly_id' => $shellyId,
            'event' => $payload['event'] ?? 'unknown',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'shelly_id' => $shellyId,
        ]);
    }
}
