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
        
        // Get latest readings for each sensor
        $sensorReadings = [];
        foreach ($sensors as $sensor) {
            $sensorId = $sensor['id'] ?? $sensor['name'] ?? null;
            if ($sensorId) {
                $sensorReadings[$sensorId] = $device->telemetryReadings()
                    ->where('sensor_id', $sensorId)
                    ->latest()
                    ->first();
            }
        }
        
        return view('devices.show', compact('device', 'logs', 'sensors', 'actuators', 'sensorReadings'));
    }
}
