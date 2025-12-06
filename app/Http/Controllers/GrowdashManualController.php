<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SprayEvent;
use App\Models\FillEvent;
use App\Models\SystemStatus;
use Illuminate\Http\Request;

class GrowdashManualController extends Controller
{
    /**
     * Manual spray control
     */
    public function manualSpray(Request $request)
    {
        // Validate token
        $token = $request->header('X-Growdash-Token');
        $validToken = config('services.growdash.webhook_token');

        if ($token !== $validToken) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'device_slug' => 'required|string',
            'action' => 'required|in:on,off',
        ]);

        $device = Device::where('slug', $request->device_slug)->firstOrFail();
        $isOn = $request->action === 'on';

        if ($isOn) {
            // Create new spray event
            SprayEvent::create([
                'device_id' => $device->id,
                'start_time' => now(),
                'manual' => true,
            ]);
        } else {
            // End current spray event
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

        // Update system status
        $this->updateSystemStatus($device, ['spray_active' => $isOn]);

        return response()->json([
            'success' => true,
            'spray_active' => $isOn,
        ]);
    }

    /**
     * Manual fill control
     */
    public function manualFill(Request $request)
    {
        // Validate token
        $token = $request->header('X-Growdash-Token');
        $validToken = config('services.growdash.webhook_token');

        if ($token !== $validToken) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'device_slug' => 'required|string',
            'action' => 'required|in:start,stop',
            'target_level' => 'sometimes|numeric|min:0|max:100',
            'target_liters' => 'sometimes|numeric|min:0',
        ]);

        $device = Device::where('slug', $request->device_slug)->firstOrFail();
        $isActive = $request->action === 'start';

        if ($isActive) {
            // Create new fill event
            FillEvent::create([
                'device_id' => $device->id,
                'start_time' => now(),
                'manual' => true,
                'target_level' => $request->target_level ?? null,
                'target_liters' => $request->target_liters ?? null,
            ]);
        } else {
            // End current fill event
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

        // Update system status
        $this->updateSystemStatus($device, ['filling_active' => $isActive]);

        return response()->json([
            'success' => true,
            'filling_active' => $isActive,
        ]);
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
