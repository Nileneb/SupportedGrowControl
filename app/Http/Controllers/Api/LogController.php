<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    /**
     * Store device logs from agent
     * POST /api/growdash/agent/logs
     *
     * Expected payload:
     * {
     *   "logs": [
     *     {
     *       "level": "info",
     *       "message": "Device booted successfully",
     *       "context": {"uptime": 120, "memory": 45000}
     *     },
     *     {
     *       "level": "error",
     *       "message": "Failed to read TDS sensor",
     *       "context": {"sensor": "tds", "error_code": "TIMEOUT"}
     *     }
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'logs' => 'required|array|min:1|max:100',
            'logs.*.level' => 'required|in:debug,info,warning,error',
            'logs.*.message' => 'required|string|max:5000',
            'logs.*.context' => 'nullable|array',
            'logs.*.timestamp' => 'nullable|string', // Agent sendet ISO8601 timestamp
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $logs = $request->input('logs');
        $inserted = [];

        foreach ($logs as $log) {
            $deviceLog = $device->deviceLogs()->create([
                'level' => $log['level'],
                'message' => $log['message'],
                'context' => $log['context'] ?? null,
                'agent_timestamp' => isset($log['timestamp']) ? $log['timestamp'] : null,
            ]);

            $inserted[] = $deviceLog->id;
        }

        // TODO: Broadcast WebSocket event for critical errors (level=error)

        Log::info('🎯 ENDPOINT_TRACKED: LogController@store', [
            'device_id' => $device->id,
            'log_count' => count($inserted),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logs stored successfully',
        ], 201);
    }
}
