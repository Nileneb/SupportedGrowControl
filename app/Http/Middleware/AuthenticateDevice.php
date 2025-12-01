<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    /**
     * Handle an incoming request.
     *
     * Validates X-Device-ID and X-Device-Token headers.
     * Sets $request->device for controllers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $publicId = $request->header('X-Device-ID');
        $deviceToken = $request->header('X-Device-Token');

        if (!$publicId || !$deviceToken) {
            return response()->json([
                'error' => 'Missing device credentials',
                'message' => 'X-Device-ID and X-Device-Token headers are required',
            ], 401);
        }

        $device = Device::where('public_id', $publicId)
            ->whereNotNull('user_id')
            ->whereNotNull('paired_at')
            ->first();

        if (!$device) {
            return response()->json([
                'error' => 'Device not found',
                'message' => 'Invalid device ID or device not paired',
            ], 404);
        }

        if (!$device->verifyAgentToken($deviceToken)) {
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => 'Device token verification failed',
            ], 403);
        }

        // Attach device to request for controller access
        $request->setUserResolver(fn () => $device);

        return $next($request);
    }
}

