<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AgentController extends Controller
{
    /**
     * Get available serial ports from device agent
     * GET /api/growdash/agent/ports
     *
     * Proxies the port scan request to the device agent's local API
     */
    public function getPorts(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        if ($device && $device->ip_address) {
            try {
                $response = Http::timeout(10)
                    ->get("http://{$device->ip_address}:8000/ports");

                if ($response->successful()) {
                    return response()->json($response->json());
                }

                return response()->json([
                    'error' => 'Failed to fetch ports from device',
                    'status' => $response->status()
                ], 502);

            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Device unreachable',
                    'message' => 'Could not connect to device'
                ], 503);
            }
        }

        // Fallback: return common ports if device has no IP
        return response()->json([
            'success' => true,
            'ports' => [
                ['port' => '/dev/ttyACM0', 'description' => 'Arduino Uno (Standard)'],
                ['port' => '/dev/ttyUSB0', 'description' => 'USB-Serial (Standard)'],
            ],
            'count' => 2,
            'fallback' => true
        ]);
    }
}
