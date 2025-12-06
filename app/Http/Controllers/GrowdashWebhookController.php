<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\ArduinoLog;
use App\Models\WaterLevel;
use App\Models\TdsReading;
use App\Models\TemperatureReading;
use App\Models\SprayEvent;
use App\Models\FillEvent;
use App\Models\SystemStatus;
use Illuminate\Http\Request;

class GrowdashWebhookController extends Controller
{
    /**
     * Handle incoming webhook log from Arduino/Growdash
     */
    public function log(Request $request)
    {
        // Validate token
        $token = $request->header('X-Growdash-Token');
        $validToken = config('services.growdash.webhook_token');

        if ($token !== $validToken) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'device_slug' => 'required|string',
            'message' => 'required|string',
            'level' => 'sometimes|string|in:debug,info,warning,error',
        ]);

        // Find or create device
        $device = Device::firstOrCreate(
            ['slug' => $request->device_slug],
            ['name' => $request->device_slug]
        );

        // Create log entry
        ArduinoLog::create([
            'device_id' => $device->id,
            'message' => $request->message,
            'level' => $request->level ?? 'info',
        ]);

        // Parse message and update relevant data
        $this->parseAndStoreData($device, $request->message);

        return response()->json(['success' => true]);
    }

    /**
     * Parse log message and store relevant data
     */
    protected function parseAndStoreData(Device $device, string $message)
    {
        // Water Level: XX.X
        if (preg_match('/WaterLevel:\s*([\d.]+)/i', $message, $matches)) {
            $level = (float) $matches[1];

            WaterLevel::create([
                'device_id' => $device->id,
                'measured_at' => now(),
                'level_percent' => $level,
                'liters' => $level * 0.2, // Approximate conversion
            ]);

            $this->updateSystemStatus($device, ['water_level' => $level]);
        }

        // TDS: XX.X
        if (preg_match('/TDS:\s*([\d.]+)/i', $message, $matches)) {
            $tds = (float) $matches[1];

            TdsReading::create([
                'device_id' => $device->id,
                'measured_at' => now(),
                'value_ppm' => $tds,
            ]);

            $this->updateSystemStatus($device, ['last_tds' => $tds]);
        }

        // Temp: XX.X
        if (preg_match('/Temp:\s*([\d.]+)/i', $message, $matches)) {
            $temp = (float) $matches[1];

            TemperatureReading::create([
                'device_id' => $device->id,
                'measured_at' => now(),
                'value_c' => $temp,
            ]);

            $this->updateSystemStatus($device, ['last_temperature' => $temp]);
        }

        // Spray: ON/OFF
        if (preg_match('/Spray:\s*(ON|OFF)/i', $message, $matches)) {
            $isOn = strtoupper($matches[1]) === 'ON';

            if ($isOn) {
                SprayEvent::create([
                    'device_id' => $device->id,
                    'start_time' => now(),
                    'manual' => false,
                ]);
            } else {
                $event = $device->sprayEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();

                if ($event) {
                    $event->update([
                        'end_time' => now(),
                        'duration_seconds' => now()->diffInSeconds($event->start_time),
                    ]);
                }
            }

            $this->updateSystemStatus($device, ['spray_active' => $isOn]);
        }

        // Filling: ON/OFF
        if (preg_match('/Filling:\s*(ON|OFF)/i', $message, $matches)) {
            $isOn = strtoupper($matches[1]) === 'ON';

            if ($isOn) {
                FillEvent::create([
                    'device_id' => $device->id,
                    'start_time' => now(),
                    'manual' => false,
                ]);
            } else {
                $event = $device->fillEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();

                if ($event) {
                    $event->update([
                        'end_time' => now(),
                    ]);
                }
            }

            $this->updateSystemStatus($device, ['filling_active' => $isOn]);
        }
    }

    /**
     * Update or create system status
     */
    protected function updateSystemStatus(Device $device, array $data)
    {
        $status = $device->systemStatuses()->latest('measured_at')->first();

        if (!$status || $status->measured_at->diffInMinutes(now()) > 5) {
            // Create new status
            $defaults = [
                'device_id' => $device->id,
                'measured_at' => now(),
                'water_level' => 0,
                'water_liters' => 0,
                'spray_active' => false,
                'filling_active' => false,
                'last_tds' => null,
                'last_temperature' => null,
            ];

            SystemStatus::create(array_merge($defaults, $data));
        } else {
            // Update existing status
            $status->update($data);
        }
    }
}
