<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;
use App\Models\ArduinoLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class DeviceLogsController extends Controller
{
    /**
     * Get device logs with filtering and pagination
     */
    public function index(Request $request, Device $device)
    {
        // Authorize: Device belongs to authenticated user
        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $query = ArduinoLog::where('device_id', $deviceId);

        // Filter by log type if provided
        if ($request->has('type')) {
            $query->where('log_type', $request->input('type'));
        }

        // Filter by level if provided
        if ($request->has('level')) {
            $query->where('level', $request->input('level'));
        }

        // Search in message
        if ($request->has('search')) {
            $query->where('message', 'like', '%' . $request->input('search') . '%');
        }

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get(['id', 'log_type', 'level', 'message', 'created_at']);

        Log::info('🎯 ENDPOINT_TRACKED: DeviceLogsController@index', [
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'log_count' => $logs->count(),
        ]);

        return response()->json([
            'logs' => $logs,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'status' => $device->status,
            ]
        ]);
    }

    /**
     * Get log statistics
     */
    public function stats(Request $request, Device $device)
    {
        // Authorize: Device belongs to authenticated user
        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $stats = ArduinoLog::where('device_id', $deviceId)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        Log::info('🎯 ENDPOINT_TRACKED: DeviceLogsController@stats', [
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
        ]);

        return response()->json($stats);
    }

    /**
     * Clear device logs
     */
    public function clear(Request $request, Device $device)
    {
        // Authorize: Device belongs to authenticated user
        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $count = ArduinoLog::where('device_id', $deviceId)->delete();

        Log::info('🎯 ENDPOINT_TRACKED: DeviceLogsController@clear', [
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'deleted_count' => $count,
        ]);

        return response()->json([
            'message' => "Cleared $count logs",
            'deleted' => $count
        ]);
    }

    /**
     * Export logs
     */
    public function export(Request $request, Device $device)
    {
        // Authorize: Device belongs to authenticated user
        if ($device->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $format = $request->input('format', 'json'); // json, csv

        $logs = ArduinoLog::where('device_id', $deviceId)
            ->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 1000))
            ->get();

        if ($format === 'csv') {
            $filename = "device-{$deviceId}-logs-" . now()->format('Y-m-d-His') . ".csv";
            
            $csv = "Timestamp,Type,Level,Message\n";
            foreach ($logs as $log) {
                $csv .= sprintf(
                    '"%s","%s","%s","%s"',
                    $log->created_at,
                    $log->log_type,
                    $log->level,
                    str_replace('"', '""', $log->message)
                ) . "\n";
            }

            Log::info('🎯 ENDPOINT_TRACKED: DeviceLogsController@export', [
                'user_id' => $request->user()->id,
                'device_id' => $device->id,
                'format' => 'csv',
                'log_count' => $logs->count(),
            ]);

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"$filename\"");
        }

        // JSON format
        Log::info('🎯 ENDPOINT_TRACKED: DeviceLogsController@export', [
            'user_id' => $request->user()->id,
            'device_id' => $device->id,
            'format' => 'json',
            'log_count' => $logs->count(),
        ]);

        return response()->json($logs);
    }
}
