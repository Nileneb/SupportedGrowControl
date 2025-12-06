<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShellyDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShellyControlController extends Controller
{
    /**
     * Control Shelly device (on/off/toggle).
     */
    public function control(Request $request, ShellyDevice $shelly, string $action): JsonResponse
    {
        $user = $request->user();

        if (!$user || $shelly->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $action = strtolower($action);
        if (!in_array($action, ['on', 'off', 'toggle'], true)) {
            return response()->json(['message' => 'Invalid action'], 422);
        }

        $result = match ($action) {
            'on' => $shelly->turnOn(),
            'off' => $shelly->turnOff(),
            'toggle' => $shelly->toggle(),
        };

        if (!($result['success'] ?? false)) {
            return response()->json([
                'message' => 'Shelly command failed',
                'error' => $result['error'] ?? 'Unknown error',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'response' => $result['response'] ?? null,
        ]);
    }
}
