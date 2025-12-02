<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

class DeviceViewController extends Controller
{
    public function show(Request $request, string $deviceId)
    {
        // Optimize: Only select needed columns, don't load relationships
        $device = Device::select([
            'id', 'public_id', 'name', 'status', 'user_id', 'bootstrap_id',
            'last_seen_at', 'capabilities', 'device_info', 'board_type', 'paired_at'
        ])
            ->where('public_id', $deviceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        
        // Don't load logs on initial page load - use AJAX instead
        $logs = [];
        
        // Get capabilities
        $capabilities = $device->capabilities ?? [];
        $sensors = $capabilities['sensors'] ?? [];
        $actuators = $capabilities['actuators'] ?? [];
        
        // Don't load readings on initial page load - WebSocket will populate them
        $sensorReadings = [];
        
        // Use new modular view (v2) - can be toggled via query param for testing
        $viewName = $request->query('view', 'v2') === 'v1' ? 'devices.show' : 'devices.show-v2';
        
        return view($viewName, compact('device', 'logs', 'sensors', 'actuators', 'sensorReadings'));
    }
}
