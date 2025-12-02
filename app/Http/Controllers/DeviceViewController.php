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
        
        return view('devices.show', compact('device', 'logs'));
    }
}
