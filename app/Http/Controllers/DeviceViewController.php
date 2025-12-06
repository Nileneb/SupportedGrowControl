<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceViewController extends Controller
{
    public function show(Request $request, string $deviceId)
    {
        // Optimize: Only select needed columns, don't load relationships
        $device = Device::select([
            'id', 'public_id', 'name', 'status', 'user_id', 'bootstrap_id',
            'last_seen_at', 'capabilities', 'device_info', 'board_type', 'paired_at',
            'created_at', 'updated_at', 'shelly_device_id', 'shelly_auth_token',
            'shelly_config', 'shelly_last_webhook_at'
        ])
            ->where('public_id', $deviceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        
        // Get capabilities
        $capabilities = $device->capabilities ?? [];
        $sensors = $capabilities['sensors'] ?? [];
        $actuators = $capabilities['actuators'] ?? [];
        
        // Don't load readings/logs on initial page load - WebSocket + AJAX will populate them
        $sensorReadings = [];
        
        // Use new workstation view with flexible layout
        $viewName = 'devices.show-workstation';
        
        Log::info('🎯 ENDPOINT_TRACKED: DeviceViewController@show', [
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'view' => $viewName,
        ]);
        
        return view($viewName, compact('device', 'sensors', 'actuators', 'sensorReadings'));
    }

    public function destroy(Request $request, string $deviceId)
    {
        $device = Device::where('public_id', $deviceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $name = $device->name;
        $device->delete();

        Log::info('🎯 ENDPOINT_TRACKED: DeviceViewController@destroy', [
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'public_id' => $device->public_id,
        ]);

        return redirect()->route('devices.index')->with('status', "Device '{$name}' deleted");
    }
}
