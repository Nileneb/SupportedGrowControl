<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ShellySyncController extends Controller
{
    /**
     * Setup Shelly integration for a device.
     */
    public function setup(Request $request, Device $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'shelly_device_id' => 'required|string|max:255',
            'ip_address' => 'nullable|ip',
        ]);

        // Generate auth token
        $token = Str::random(32);

        // Update device
        $device->update([
            'shelly_device_id' => $validated['shelly_device_id'],
            'shelly_auth_token' => $token,
            'shelly_config' => [
                'configured_at' => now()->toIso8601String(),
                'configured_by' => auth()->id(),
                'ip_address' => $validated['ip_address'] ?? null,
            ],
        ]);

        // Generate webhook URL
        $webhookUrl = route('api.shelly.webhook', $device->public_id) . '?token=' . $token;

        Log::info('🎯 ENDPOINT_TRACKED: ShellySyncController@setup', [
            'user_id' => auth()->id(),
            'device_id' => $device->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shelly integration configured successfully',
            'webhook_url' => $webhookUrl,
            'instructions' => [
                '1. Open your Shelly device web interface',
                '2. Navigate to Settings → Actions/Webhooks',
                '3. Add a new webhook with the URL provided above',
                '4. Configure which events should trigger the webhook',
            ],
        ]);
    }

    /**
     * Update Shelly integration settings.
     */
    public function update(Request $request, Device $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'shelly_device_id' => 'required|string|max:255',
        ]);

        // Update device (keep existing token)
        $device->update([
            'shelly_device_id' => $validated['shelly_device_id'],
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: ShellySyncController@update', [
            'user_id' => auth()->id(),
            'device_id' => $device->id,
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: ShellySyncController@update', [
            'user_id' => auth()->id(),
            'device_id' => $device->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shelly configuration updated successfully',
        ]);
    }

    /**
     * Remove Shelly integration.
     */
    public function remove(Request $request, Device $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Clear Shelly fields
        $device->update([
            'shelly_device_id' => null,
            'shelly_auth_token' => null,
            'shelly_config' => null,
            'shelly_last_webhook_at' => null,
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: ShellySyncController@remove', [
            'user_id' => auth()->id(),
            'device_id' => $device->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shelly integration removed successfully',
        ]);
    }

    /**
     * Send control command to Shelly device.
     */
    public function control(Request $request, Device $device): JsonResponse
    {
        // Verify ownership
        if ($device->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$device->hasShellyIntegration()) {
            return response()->json(['error' => 'Shelly integration not configured'], 400);
        }

        $validated = $request->validate([
            'action' => 'required|in:on,off,toggle',
        ]);

        $shellyDeviceId = $device->shelly_device_id;
        $action = $validated['action'];

        // Extract IP from device_id or use stored IP
        // Shelly device ID format: shellyplug-s-XXXXX or shellyplus1pm-XXXXX
        // We need the IP address - check if it's stored in shelly_config
        $shellyIp = $device->shelly_config['ip_address'] ?? null;

        if (!$shellyIp) {
            return response()->json([
                'error' => 'Shelly device IP address not configured',
                'message' => 'Please add the Shelly device IP address to the configuration',
                'instructions' => 'Update the device configuration with: {"ip_address": "192.168.x.x"}',
            ], 400);
        }

        // Determine API endpoint based on Shelly generation
        // Gen1: /relay/0?turn=on
        // Gen2/Plus: /rpc/Switch.Set?id=0&on=true
        $isGen2 = str_contains($shellyDeviceId, 'plus') || str_contains($shellyDeviceId, 'pro');

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 5]);

            if ($isGen2) {
                // Shelly Gen2/Plus API
                $url = "http://{$shellyIp}/rpc/Switch.Set";
                $params = [
                    'id' => 0,
                    'on' => $action === 'on' ? true : ($action === 'off' ? false : null),
                ];
                if ($action === 'toggle') {
                    $params = ['id' => 0, 'toggle' => true];
                }

                $response = $client->post($url, [
                    'json' => $params,
                ]);
            } else {
                // Shelly Gen1 API
                $url = "http://{$shellyIp}/relay/0";
                $response = $client->get($url, [
                    'query' => ['turn' => $action],
                ]);
            }

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode === 200) {
                // Store last command in config
                $config = $device->shelly_config ?? [];
                $config['last_command'] = [
                    'action' => $action,
                    'timestamp' => now()->toIso8601String(),
                    'response' => $body,
                ];
                $device->update(['shelly_config' => $config]);

                Log::info('🎯 ENDPOINT_TRACKED: ShellySyncController@control', [
                    'user_id' => auth()->id(),
                    'device_id' => $device->id,
                    'action' => $action,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Shelly device turned {$action}",
                    'response' => $body,
                ]);
            }

            return response()->json([
                'error' => 'Unexpected response from Shelly device',
                'status_code' => $statusCode,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to communicate with Shelly device',
                'message' => $e->getMessage(),
                'hint' => 'Make sure the device is reachable at ' . $shellyIp,
            ], 500);
        }
    }
}
