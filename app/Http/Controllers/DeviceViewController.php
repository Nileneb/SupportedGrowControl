<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

class DeviceViewController extends Controller
{
    public function show(Request $request, string $deviceId)
    {
        $device = Device::where('public_id', $deviceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        
        $logs = $device->arduinoLogs()
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        // Get capabilities
        $capabilities = $device->capabilities ?? [];
        $sensors = $capabilities['sensors'] ?? [];
        $actuators = $capabilities['actuators'] ?? [];
        
        // Get latest readings for each sensor with proper structure
        $sensorReadings = [];
        foreach ($sensors as $sensor) {
            $sensorId = $sensor['id'] ?? $sensor['name'] ?? null;
            if ($sensorId) {
                $latestReading = $device->telemetryReadings()
                    ->where('sensor_key', $sensorId)
                    ->latest()
                    ->first();
                
                if ($latestReading) {
                    $sensorReadings[$sensorId] = [
                        'value' => $latestReading->value,
                        'timestamp' => $latestReading->created_at
                    ];
                }
            }
        }
        
        // Use new modular view (v2) - can be toggled via query param for testing
        $viewName = $request->query('view', 'v2') === 'v1' ? 'devices.show' : 'devices.show-v2';
        
        return view($viewName, compact('device', 'logs', 'sensors', 'actuators', 'sensorReadings'));
    }
}
