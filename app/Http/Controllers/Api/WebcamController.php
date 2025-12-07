<?php

namespace App\Http\Controllers\Api;

use App\Models\WebcamFeed;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class WebcamController extends Controller
{
    /**
     * Agent meldet verfügbare Webcams für ein Device
     * 
     * POST /api/growdash/agent/webcams
     * 
     * Body:
     * {
     *   "webcams": [
     *     {
     *       "device_path": "/dev/video0",
     *       "stream_endpoint": "http://127.0.0.1:8090/stream/webcam?device=/dev/video0",
     *       "name": "USB Camera (046d:0825)"
     *     }
     *   ]
     * }
     */
    public function registerFromAgent(Request $request)
    {
        // Device-Auth bereits durch device.auth Middleware validiert
        $device = $request->attributes->get('device'); // Aus Middleware Attributes lesen
        
        $validated = $request->validate([
            'webcams' => 'required|array',
            'webcams.*.device_path' => 'required|string',
            'webcams.*.stream_endpoint' => 'required|url',
            'webcams.*.name' => 'nullable|string',
        ]);
        
        $registered = [];
        $updated = [];
        
        foreach ($validated['webcams'] as $webcam) {
            $device_path = $webcam['device_path'];
            $stream_url = $webcam['stream_endpoint'];
            $name = $webcam['name'] ?? "Camera {$device_path}";
            
            // Prüfen ob Webcam bereits existiert (unique: device_id + device_path)
            $existing = WebcamFeed::where('device_id', $device->id)
                ->where('device_path', $device_path)
                ->first();
            
            if ($existing) {
                // Update existing webcam
                $existing->update([
                    'name' => $name,
                    'stream_url' => $stream_url,
                    'is_active' => true,
                ]);
                $updated[] = $existing;
                
                Log::info("Webcam updated", [
                    'device_id' => $device->id,
                    'webcam_id' => $existing->id,
                    'device_path' => $device_path,
                ]);
            } else {
                // Create new webcam
                $webcamFeed = WebcamFeed::create([
                    'user_id' => $device->user_id,
                    'device_id' => $device->id,
                    'device_path' => $device_path,
                    'name' => $name,
                    'stream_url' => $stream_url,
                    'type' => 'mjpeg',
                    'is_active' => true,
                ]);
                $registered[] = $webcamFeed;
                
                Log::info("Webcam registered", [
                    'device_id' => $device->id,
                    'webcam_id' => $webcamFeed->id,
                    'device_path' => $device_path,
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'registered' => count($registered),
            'updated' => count($updated),
            'total' => count($registered) + count($updated),
            'message' => sprintf(
                'Registered %d new, updated %d existing webcams',
                count($registered),
                count($updated)
            ),
        ]);
    }
    
    /**
     * Liste aller Webcams für ein Device (für Browser-Nutzer)
     * 
     * GET /api/devices/{device}/webcams
     * Auth: Session oder Sanctum (Browser-Nutzer oder API-Client)
     */
    public function listForDevice(Device $device)
    {
        // Umfassendes Debugging der Session/Auth-Situation
        $debugInfo = [
            'endpoint' => 'GET /api/devices/{device}/webcams',
            'timestamp' => now()->toIso8601String(),
            'device_id' => $device->id,
            'device_uuid' => $device->public_id,
            
            // Cookie-Info
            'cookies_received' => request()->header('cookie') ? 'YES' : 'NO',
            'cookie_count' => count($_COOKIE),
            'laravel_session_cookie' => isset($_COOKIE['XSRF-TOKEN']) ? 'YES' : 'NO',
            
            // Session-Info
            'session_id' => session()->getId(),
            'session_has_driver' => session()->getDefaultDriver(),
            'session_exists' => session()->exists('_token') ? 'YES' : 'NO',
            
            // Auth Guards
            'auth_web_check' => auth('web')->check() ? 'YES' : 'NO',
            'auth_web_id' => auth('web')->id(),
            'auth_web_user' => auth('web')->user() ? auth('web')->user()->email : 'NULL',
            'auth_sanctum_check' => auth('sanctum')->check() ? 'YES' : 'NO',
            'auth_sanctum_id' => auth('sanctum')->id(),
            
            // Request Headers
            'authorization_header' => request()->header('authorization') ? 'YES (Bearer/Token)' : 'NO',
            'csrf_token_in_header' => request()->header('X-CSRF-TOKEN') ? 'YES' : 'NO',
            'user_agent' => request()->header('user-agent'),
        ];
        
        \Illuminate\Support\Facades\Log::info('🔍 Webcam API Auth Debug', $debugInfo);
        
        // Authentifizierung: Session ODER Sanctum Token
        if (!auth('web')->check() && !auth('sanctum')->check()) {
            \Illuminate\Support\Facades\Log::warning('❌ Webcam API Auth Failed', [
                'reason' => 'Neither web nor sanctum auth passed',
                'debug' => $debugInfo,
            ]);
            
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please login or provide a valid API token',
                'debug' => config('app.debug') ? $debugInfo : null,
            ], 401);
        }
        
        \Illuminate\Support\Facades\Log::info('✅ Webcam API Auth Success', [
            'auth_method' => auth('web')->check() ? 'session' : 'sanctum',
            'user_id' => auth('web')->id() ?? auth('sanctum')->id(),
        ]);
        
        $webcams = WebcamFeed::where('device_id', $device->id)
            ->where('is_active', true)
            ->get();
        
        return response()->json([
            'success' => true,
            'webcams' => $webcams,
        ]);
    }

    /**
     * Liste aller Webcams für das Device (für Agent über device.auth Middleware)
     * 
     * GET /api/growdash/agent/webcams
     * Auth: Device-Token (X-Device-ID + X-Device-Token Header)
     */
    public function indexForAgent(Request $request)
    {
        // Device wurde durch device.auth Middleware validiert
        $device = $request->device;
        
        $webcams = WebcamFeed::where('device_id', $device->id)
            ->where('is_active', true)
            ->get();
        
        return response()->json([
            'success' => true,
            'webcams' => $webcams,
        ]);
    }
    
    /**
     * Alte Methode (für Rückwärtskompatibilität)
     * 
     * GET /api/devices/{device}/webcams
     */
    public function index(Device $device)
    {
        // Authorization: User muss Owner des Devices sein
        $this->authorize('view', $device);
        
        $webcams = WebcamFeed::where('device_id', $device->id)
            ->where('is_active', true)
            ->get();
        
        return response()->json([
            'success' => true,
            'webcams' => $webcams,
        ]);
    }
    
    /**
     * Webcam aktivieren/deaktivieren
     * 
     * PATCH /api/webcams/{webcam}
     */
    public function update(Request $request, WebcamFeed $webcam)
    {
        // Authentifizierung: Session ODER Sanctum Token
        if (!auth('web')->check() && !auth('sanctum')->check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please login or provide a valid API token'
            ], 401);
        }
        
        // Authorization: User muss Owner sein
        $userId = auth('web')->id() ?? auth('sanctum')->id();
        if ($webcam->user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'is_active' => 'sometimes|boolean',
            'name' => 'sometimes|string|max:255',
            'refresh_interval' => 'sometimes|integer|min:100',
        ]);
        
        $webcam->update($validated);
        
        return response()->json([
            'success' => true,
            'webcam' => $webcam,
        ]);
    }
    
    /**
     * Webcam löschen
     * 
     * DELETE /api/webcams/{webcam}
     */
    public function destroy(WebcamFeed $webcam)
    {
        // Authentifizierung: Session ODER Sanctum Token
        if (!auth('web')->check() && !auth('sanctum')->check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Please login or provide a valid API token'
            ], 401);
        }
        
        // Authorization: User muss Owner sein
        $userId = auth('web')->id() ?? auth('sanctum')->id();
        if ($webcam->user_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $webcam->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Webcam deleted',
        ]);
    }
}
