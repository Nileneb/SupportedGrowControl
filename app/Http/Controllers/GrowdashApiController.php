<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

class GrowdashApiController extends Controller
{
    /**
     * Get current system status for a device
     */
    public function status(Request $request)
    {
        $device = Device::where('slug', $request->device_slug)->firstOrFail();

        // Check authorization
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
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
            ]);
        }

        return response()->json([
            'water_level' => $status->water_level,
            'water_liters' => $status->water_liters,
            'spray_active' => $status->spray_active,
            'filling_active' => $status->filling_active,
            'last_tds' => $status->last_tds,
            'last_temperature' => $status->last_temperature,
        ]);
    }

    /**
     * Get water level history
     */
    public function waterHistory(Request $request)
    {
        $device = Device::where('slug', $request->device_slug)->firstOrFail();

        if ($device->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 100);

        $history = $device->waterLevels()
            ->latest('measured_at')
            ->limit($limit)
            ->get()
            ->map(fn($record) => [
                'timestamp' => $record->measured_at->toIso8601String(),
                'level' => $record->level_percent,
                'liters' => $record->liters,
            ]);

        return response()->json(['history' => $history]);
    }

    /**
     * Get TDS history
     */
    public function tdsHistory(Request $request)
    {
        $device = Device::where('slug', $request->device_slug)->firstOrFail();

        if ($device->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 100);

        $history = $device->tdsReadings()
            ->latest('measured_at')
            ->limit($limit)
            ->get()
            ->map(fn($record) => [
                'timestamp' => $record->measured_at->toIso8601String(),
                'value' => $record->value_ppm,
            ]);

        return response()->json(['history' => $history]);
    }

    /**
     * Get temperature history
     */
    public function temperatureHistory(Request $request)
    {
        $device = Device::where('slug', $request->device_slug)->firstOrFail();

        if ($device->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 100);

        $history = $device->temperatureReadings()
            ->latest('measured_at')
            ->limit($limit)
            ->get()
            ->map(fn($record) => [
                'timestamp' => $record->measured_at->toIso8601String(),
                'value' => $record->value_c,
            ]);

        return response()->json(['history' => $history]);
    }

    /**
     * Get spray events
     */
    public function sprayEvents(Request $request)
    {
        $device = Device::where('slug', $request->device_slug)->firstOrFail();

        if ($device->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $events = $device->sprayEvents()
            ->latest('start_time')
            ->limit(50)
            ->get()
            ->map(fn($event) => [
                'start_time' => $event->start_time->toIso8601String(),
                'end_time' => $event->end_time?->toIso8601String(),
                'duration_seconds' => $event->duration_seconds,
                'manual' => $event->manual,
            ]);

        return response()->json(['events' => $events]);
    }

    /**
     * Get fill events
     */
    public function fillEvents(Request $request)
    {
        $device = Device::where('slug', $request->device_slug)->firstOrFail();

        if ($device->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $events = $device->fillEvents()
            ->latest('start_time')
            ->limit(50)
            ->get()
            ->map(fn($event) => [
                'start_time' => $event->start_time->toIso8601String(),
                'end_time' => $event->end_time?->toIso8601String(),
                'target_level' => $event->target_level,
                'target_liters' => $event->target_liters,
                'manual' => $event->manual,
            ]);

        return response()->json(['events' => $events]);
    }

    /**
     * Get Arduino logs
     */
    public function logs(Request $request)
    {
        $device = Device::where('slug', $request->device_slug)->firstOrFail();

        if ($device->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $device->arduinoLogs()->latest('created_at');

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        $logs = $query->limit(100)->get()->map(fn($log) => [
            'timestamp' => $log->created_at->toIso8601String(),
            'level' => $log->level,
            'message' => $log->message,
        ]);

        return response()->json(['logs' => $logs]);
    }
}
