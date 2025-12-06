<?php

namespace App\Http\Controllers;

use App\Models\ShellyDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShellyWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Shelly device.
     */
    public function handleWebhook(Request $request, ShellyDevice $shelly): JsonResponse
    {
        // Verify token from query parameter
        $token = $request->query('token');
        
        if (!$token || !$shelly->verifyToken($token)) {
            return response()->json([
                'error' => 'Invalid or missing token',
            ], 403);
        }

        // Record webhook timestamp
        $shelly->recordWebhook();

        // Get webhook payload
        $payload = $request->all();

        // Store payload in config for later processing
        $shelly->update([
            'config' => array_merge($shelly->config ?? [], [
                'last_webhook_payload' => $payload,
                'last_webhook_timestamp' => now()->toIso8601String(),
            ]),
        ]);

        // TODO: Process webhook data (switch state, power consumption, etc.)
        // This will be implemented when we add event automation

        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
        ], 200);
    }
}
