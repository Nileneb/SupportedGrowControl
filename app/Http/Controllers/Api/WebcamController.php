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
        $device = $request->device; // Injected durch Middleware
        
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
     * Liste aller Webcams für ein Device (für Frontend)
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
        // Authorization: User muss Owner sein
        if ($webcam->user_id !== auth()->id()) {
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
        // Authorization: User muss Owner sein
        if ($webcam->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $webcam->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Webcam deleted',
        ]);
    }
}
