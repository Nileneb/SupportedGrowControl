<?php

namespace App\Http\Controllers;

use App\Models\ArduinoLog;
use App\Models\Device;
use App\Models\FillEvent;
use App\Models\SprayEvent;
use App\Models\SystemStatus;
use App\Models\TdsReading;
use App\Models\TemperatureReading;
use App\Models\WaterLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrowdashWebhookController extends Controller
{
    /**
     * Find or create a device by slug.
     */
    protected function findDevice(string $slug): Device
    {
        return Device::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug]
        );
    }

    /**
     * Receive and process a log message from Growdash device.
     *
     * POST /api/growdash/log
     */
    public function log(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_slug' => 'required|string',
            'message' => 'required|string',
            'level' => 'nullable|string|in:debug,info,warning,error',
        ]);

        $device = $this->findDevice($data['device_slug']);

        $log = ArduinoLog::create([
            'device_id' => $device->id,
            'level' => $data['level'] ?? 'info',
            'message' => $data['message'],
            'logged_at' => now(),
        ]);

        // Parse the message for structured data
        $this->parseMessage($device, $log->message);

        return response()->json(['success' => true, 'log_id' => $log->id]);
    }

    /**
     * Receive structured events from Growdash device (optional).
     *
     * POST /api/growdash/event
     */
    public function event(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_slug' => 'required|string',
            'type' => 'required|string',
            'payload' => 'required|array',
        ]);

        $device = $this->findDevice($data['device_slug']);
        $type = $data['type'];
        $p = $data['payload'];

        switch ($type) {
            case 'water_level':
                WaterLevel::create([
                    'device_id' => $device->id,
                    'measured_at' => $p['measured_at'] ?? now(),
                    'level_percent' => $p['level_percent'],
                    'liters' => $p['liters'] ?? null,
                ]);
                $this->updateStatus($device, [
                    'water_level' => $p['level_percent'],
                    'water_liters' => $p['liters'] ?? null,
                ]);
                break;

            case 'tds':
                TdsReading::create([
                    'device_id' => $device->id,
                    'measured_at' => $p['measured_at'] ?? now(),
                    'value_ppm' => $p['value_ppm'],
                ]);
                $this->updateStatus($device, ['last_tds' => $p['value_ppm']]);
                break;

            case 'temperature':
                TemperatureReading::create([
                    'device_id' => $device->id,
                    'measured_at' => $p['measured_at'] ?? now(),
                    'value_c' => $p['value_c'],
                ]);
                $this->updateStatus($device, ['last_temperature' => $p['value_c']]);
                break;

            case 'spray_start':
                SprayEvent::create([
                    'device_id' => $device->id,
                    'start_time' => $p['start_time'] ?? now(),
                    'manual' => $p['manual'] ?? false,
                ]);
                $this->updateStatus($device, ['spray_active' => true]);
                break;

            case 'spray_end':
                $event = $device->sprayEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();
                if ($event) {
                    $event->update([
                        'end_time' => $p['end_time'] ?? now(),
                        'duration_seconds' => $event->start_time->diffInSeconds($p['end_time'] ?? now()),
                    ]);
                }
                $this->updateStatus($device, ['spray_active' => false]);
                break;

            case 'fill_start':
                FillEvent::create([
                    'device_id' => $device->id,
                    'start_time' => $p['start_time'] ?? now(),
                    'target_level' => $p['target_level'] ?? null,
                    'target_liters' => $p['target_liters'] ?? null,
                    'manual' => $p['manual'] ?? false,
                ]);
                $this->updateStatus($device, ['filling_active' => true]);
                break;

            case 'fill_end':
                $event = $device->fillEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();
                if ($event) {
                    $event->update([
                        'end_time' => $p['end_time'] ?? now(),
                        'duration_seconds' => $event->start_time->diffInSeconds($p['end_time'] ?? now()),
                        'actual_liters' => $p['actual_liters'] ?? null,
                    ]);
                }
                $this->updateStatus($device, ['filling_active' => false]);
                break;
        }

        return response()->json(['success' => true]);
    }

    /**
     * Parse log messages to extract structured data.
     */
    protected function parseMessage(Device $device, string $message): void
    {
        // Water Level: "WaterLevel: 75.3" or "Water: 75.3%"
        if (preg_match('/(?:WaterLevel|Water):\s*([\d.]+)(?:%)?/i', $message, $matches)) {
            $percent = (float) $matches[1];
            $wl = WaterLevel::create([
                'device_id' => $device->id,
                'measured_at' => now(),
                'level_percent' => $percent,
                'liters' => null, // Could calculate based on tank dimensions
            ]);
            $this->updateStatus($device, [
                'water_level' => $wl->level_percent,
                'water_liters' => $wl->liters,
            ]);
        }

        // TDS: "TDS: 450.2" or "TDS: 450 ppm"
        if (preg_match('/TDS:\s*([\d.]+)/i', $message, $matches)) {
            $value = (float) $matches[1];
            TdsReading::create([
                'device_id' => $device->id,
                'measured_at' => now(),
                'value_ppm' => $value,
            ]);
            $this->updateStatus($device, ['last_tds' => $value]);
        }

        // Temperature: "Temp: 22.5" or "Temperature: 22.5°C"
        if (preg_match('/(?:Temp|Temperature):\s*([\d.]+)/i', $message, $matches)) {
            $temp = (float) $matches[1];
            TemperatureReading::create([
                'device_id' => $device->id,
                'measured_at' => now(),
                'value_c' => $temp,
            ]);
            $this->updateStatus($device, ['last_temperature' => $temp]);
        }

        // Spray: "Spray: ON" or "Spray: OFF"
        if (preg_match('/Spray:\s*(ON|OFF)/i', $message, $matches)) {
            $active = strtoupper($matches[1]) === 'ON';
            
            if ($active) {
                // Check if there's already an active spray event
                $existing = $device->sprayEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();
                
                if (!$existing) {
                    SprayEvent::create([
                        'device_id' => $device->id,
                        'start_time' => now(),
                        'manual' => false,
                    ]);
                }
            } else {
                // End the current spray event
                $event = $device->sprayEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();
                
                if ($event) {
                    $event->update([
                        'end_time' => now(),
                        'duration_seconds' => $event->start_time->diffInSeconds(now()),
                    ]);
                }
            }
            
            $this->updateStatus($device, ['spray_active' => $active]);
        }

        // Filling: "Filling: ON" or "Filling: OFF"
        if (preg_match('/Filling:\s*(ON|OFF)/i', $message, $matches)) {
            $active = strtoupper($matches[1]) === 'ON';
            
            if ($active) {
                $existing = $device->fillEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();
                
                if (!$existing) {
                    FillEvent::create([
                        'device_id' => $device->id,
                        'start_time' => now(),
                        'manual' => false,
                    ]);
                }
            } else {
                $event = $device->fillEvents()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();
                
                if ($event) {
                    $event->update([
                        'end_time' => now(),
                        'duration_seconds' => $event->start_time->diffInSeconds(now()),
                    ]);
                }
            }
            
            $this->updateStatus($device, ['filling_active' => $active]);
        }
    }

    /**
     * Update or create system status for device.
     */
    protected function updateStatus(Device $device, array $attributes): void
    {
        $status = $device->systemStatuses()->latest('measured_at')->first();

        if (!$status) {
            $status = new SystemStatus([
                'device_id' => $device->id,
                'measured_at' => now(),
            ]);
        }

        foreach ($attributes as $key => $value) {
            $status->{$key} = $value;
        }

        $status->measured_at = now();
        $status->save();
    }

    // ==================== Public API Endpoints ====================

    /**
     * Get current system status.
     *
     * GET /api/growdash/status?device_slug=growdash-1
     */
    public function status(Request $request): JsonResponse
    {
        $deviceSlug = $request->query('device_slug', config('services.growdash.device_slug'));
        $device = Device::where('slug', $deviceSlug)->first();

        if (!$device) {
            return response()->json([
                'water_level' => 0,
                'water_liters' => 0,
                'spray_active' => false,
                'filling_active' => false,
                'last_tds' => null,
                'last_temperature' => null,
                'timestamp' => now()->timestamp,
            ]);
        }

        $status = $device->systemStatuses()->latest('measured_at')->first();

        if (!$status) {
            return response()->json([
                'water_level' => 0,
                'water_liters' => 0,
                'spray_active' => false,
                'filling_active' => false,
                'last_tds' => null,
                'last_temperature' => null,
                'timestamp' => now()->timestamp,
            ]);
        }

        return response()->json([
            'water_level' => $status->water_level,
            'water_liters' => $status->water_liters,
            'spray_active' => $status->spray_active,
            'filling_active' => $status->filling_active,
            'last_tds' => $status->last_tds,
            'last_temperature' => $status->last_temperature,
            'timestamp' => $status->measured_at->timestamp,
        ]);
    }

    /**
     * Get water level history.
     *
     * GET /api/growdash/water-history?device_slug=growdash-1&limit=100
     */
    public function waterHistory(Request $request): JsonResponse
    {
        $deviceSlug = $request->query('device_slug');
        $limit = (int) $request->query('limit', 100);

        $device = Device::where('slug', $deviceSlug)->firstOrFail();

        $history = $device->waterLevels()
            ->latest('measured_at')
            ->limit($limit)
            ->get()
            ->map(fn (WaterLevel $wl) => [
                'timestamp' => $wl->measured_at->timestamp,
                'level' => $wl->level_percent,
                'liters' => $wl->liters,
            ]);

        return response()->json(['history' => $history]);
    }

    /**
     * Get TDS reading history.
     *
     * GET /api/growdash/tds-history?device_slug=growdash-1&limit=100
     */
    public function tdsHistory(Request $request): JsonResponse
    {
        $deviceSlug = $request->query('device_slug');
        $limit = (int) $request->query('limit', 100);

        $device = Device::where('slug', $deviceSlug)->firstOrFail();

        $history = $device->tdsReadings()
            ->latest('measured_at')
            ->limit($limit)
            ->get()
            ->map(fn (TdsReading $tds) => [
                'timestamp' => $tds->measured_at->timestamp,
                'value_ppm' => $tds->value_ppm,
            ]);

        return response()->json(['history' => $history]);
    }

    /**
     * Get temperature reading history.
     *
     * GET /api/growdash/temperature-history?device_slug=growdash-1&limit=100
     */
    public function temperatureHistory(Request $request): JsonResponse
    {
        $deviceSlug = $request->query('device_slug');
        $limit = (int) $request->query('limit', 100);

        $device = Device::where('slug', $deviceSlug)->firstOrFail();

        $history = $device->temperatureReadings()
            ->latest('measured_at')
            ->limit($limit)
            ->get()
            ->map(fn (TemperatureReading $temp) => [
                'timestamp' => $temp->measured_at->timestamp,
                'value_c' => $temp->value_c,
            ]);

        return response()->json(['history' => $history]);
    }

    /**
     * Get spray events.
     *
     * GET /api/growdash/spray-events?device_slug=growdash-1&limit=50
     */
    public function sprayEvents(Request $request): JsonResponse
    {
        $deviceSlug = $request->query('device_slug');
        $limit = (int) $request->query('limit', 50);

        $device = Device::where('slug', $deviceSlug)->firstOrFail();

        $events = $device->sprayEvents()
            ->latest('start_time')
            ->limit($limit)
            ->get()
            ->map(fn (SprayEvent $event) => [
                'start_time' => $event->start_time->timestamp,
                'end_time' => $event->end_time?->timestamp,
                'duration_seconds' => $event->duration_seconds,
                'manual' => $event->manual,
            ]);

        return response()->json(['events' => $events]);
    }

    /**
     * Get fill events.
     *
     * GET /api/growdash/fill-events?device_slug=growdash-1&limit=50
     */
    public function fillEvents(Request $request): JsonResponse
    {
        $deviceSlug = $request->query('device_slug');
        $limit = (int) $request->query('limit', 50);

        $device = Device::where('slug', $deviceSlug)->firstOrFail();

        $events = $device->fillEvents()
            ->latest('start_time')
            ->limit($limit)
            ->get()
            ->map(fn (FillEvent $event) => [
                'start_time' => $event->start_time->timestamp,
                'end_time' => $event->end_time?->timestamp,
                'duration_seconds' => $event->duration_seconds,
                'target_level' => $event->target_level,
                'target_liters' => $event->target_liters,
                'actual_liters' => $event->actual_liters,
                'manual' => $event->manual,
            ]);

        return response()->json(['events' => $events]);
    }

    /**
     * Get Arduino logs.
     *
     * GET /api/growdash/logs?device_slug=growdash-1&limit=200&level=error
     */
    public function logs(Request $request): JsonResponse
    {
        $deviceSlug = $request->query('device_slug');
        $limit = (int) $request->query('limit', 200);
        $level = $request->query('level');

        $device = Device::where('slug', $deviceSlug)->firstOrFail();

        $query = $device->arduinoLogs()->latest('logged_at');

        if ($level) {
            $query->where('level', $level);
        }

        $logs = $query->limit($limit)
            ->get()
            ->map(fn (ArduinoLog $log) => [
                'timestamp' => $log->logged_at->timestamp,
                'level' => $log->level,
                'message' => $log->message,
            ]);

        return response()->json(['logs' => $logs]);
    }

    // ==================== Manual Control Endpoints ====================

    /**
     * Manually control spray function.
     *
     * POST /api/growdash/manual-spray
     */
    public function manualSpray(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_slug' => 'required|string',
            'action' => 'required|in:on,off',
        ]);

        $device = $this->findDevice($data['device_slug']);
        $active = $data['action'] === 'on';

        if ($active) {
            // Start new spray event
            $existing = $device->sprayEvents()
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();

            if (!$existing) {
                SprayEvent::create([
                    'device_id' => $device->id,
                    'start_time' => now(),
                    'manual' => true,
                ]);
            }
        } else {
            // End current spray event
            $event = $device->sprayEvents()
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();

            if ($event) {
                $event->update([
                    'end_time' => now(),
                    'duration_seconds' => $event->start_time->diffInSeconds(now()),
                ]);
            }
        }

        $this->updateStatus($device, ['spray_active' => $active]);

        return response()->json([
            'success' => true,
            'device_slug' => $device->slug,
            'spray_active' => $active,
        ]);
    }

    /**
     * Manually control fill function.
     *
     * POST /api/growdash/manual-fill
     */
    public function manualFill(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_slug' => 'required|string',
            'action' => 'required|in:start,stop',
            'target_level' => 'nullable|numeric|min:0|max:100',
            'target_liters' => 'nullable|numeric|min:0',
        ]);

        $device = $this->findDevice($data['device_slug']);

        if ($data['action'] === 'start') {
            $existing = $device->fillEvents()
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();

            if (!$existing) {
                FillEvent::create([
                    'device_id' => $device->id,
                    'start_time' => now(),
                    'target_level' => $data['target_level'] ?? null,
                    'target_liters' => $data['target_liters'] ?? null,
                    'manual' => true,
                ]);
            }

            $this->updateStatus($device, ['filling_active' => true]);
        } else {
            $event = $device->fillEvents()
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();

            if ($event) {
                // Get current water level to calculate actual liters filled
                $currentStatus = $device->systemStatuses()->latest('measured_at')->first();
                
                $event->update([
                    'end_time' => now(),
                    'duration_seconds' => $event->start_time->diffInSeconds(now()),
                    'actual_liters' => $currentStatus?->water_liters,
                ]);
            }

            $this->updateStatus($device, ['filling_active' => false]);
        }

        return response()->json([
            'success' => true,
            'device_slug' => $device->slug,
            'filling_active' => $data['action'] === 'start',
        ]);
    }
}
