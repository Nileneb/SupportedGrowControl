<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    /**
     * Register a device via Direct-Login-Flow.
     * POST /api/growdash/devices/register
     * 
     * Expects:
     * - User authenticated via Sanctum token
    * - name (optional)
    * - platform (optional)
    * - version (optional)
    * - device_info (optional)
    * - bootstrap_id (optional, for legacy re-pair)
     */
    public function register(Request $request): JsonResponse
    {
        $user = $request->user(); // via Sanctum
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }
        
        $validated = $request->validate([
            'bootstrap_id' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'device_info' => 'nullable|array',
        ]);
        
        // Prüfen ob Device bereits existiert (für Re-Pairing)
        $device = $validated['bootstrap_id']
            ? Device::where('bootstrap_id', $validated['bootstrap_id'])->first()
            : null;
        
        if ($device) {
            // Re-Pairing: neuen Token generieren
            $agentTokenPlain = Str::random(64);
            $device->update([
                'agent_token' => hash('sha256', $agentTokenPlain),
                'status' => 'paired',
                'last_seen_at' => now(),
            ]);
            
            \Log::info('🎯 ENDPOINT_TRACKED: DeviceController@register (re-pair)', [
                'user_id' => $user->id,
                'device_id' => $device->id,
            ]);
            
            return response()->json([
                'success' => true,
                'device_id' => $device->public_id,
                'agent_token' => $agentTokenPlain,
                'message' => 'Device re-paired successfully',
            ], 200);
        }
        
        // Neue Registrierung
        $publicId = (string) Str::uuid();
        $agentTokenPlain = Str::random(64);

        // Generate unique slug
        $baseSlug = Str::slug($validated['name'] ?? 'GrowDash Device');
        $slug = $baseSlug . '-' . Str::random(8);

        $deviceInfo = $validated['device_info'] ?? [];
        if (!empty($validated['platform'])) {
            $deviceInfo['platform'] = $validated['platform'];
        }
        if (!empty($validated['version'])) {
            $deviceInfo['version'] = $validated['version'];
        }
        
        try {
            $device = Device::create([
                'user_id' => $user->id,
                'public_id' => $publicId,
                'bootstrap_id' => $validated['bootstrap_id'] ?? null,
                'name' => $validated['name'] ?? 'GrowDash Device',
                'slug' => $slug,
                'agent_token' => hash('sha256', $agentTokenPlain),
                'device_info' => $deviceInfo,
                'status' => 'paired',
                'paired_at' => now(),
                'bootstrap_code' => strtoupper(Str::random(6)),
            ]);
        } catch (\Exception $e) {
            \Log::error('Device creation failed', [
                'bootstrap_id' => $validated['bootstrap_id'],
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
        
        \Log::info('🎯 ENDPOINT_TRACKED: DeviceController@register (new)', [
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);
        
        return response()->json([
            'success' => true,
            'device_id' => $device->public_id,
            'agent_token' => $agentTokenPlain,
            'message' => 'Device registered successfully',
        ], 201);
    }
}
