<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Agent API Controller
 *
 * Simplified agent endpoints: heartbeat, command polling/results.
 * Base URL: /api/growdash/agent
 */
class AgentController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        // Validate optional payload
        $data = $request->validate([
            'last_state' => 'nullable|array',
            'logs' => 'nullable|array',
            'board_type' => 'nullable|string|max:100',
            'port' => 'nullable|string|max:255',
            'vendor_id' => 'nullable|string|max:50',
            'product_id' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        // Update device status
        $updateData = [
            'last_seen_at' => now(),
            'status' => 'online',
        ];

        // Update hardware info if provided
        if (isset($data['board_type'])) {
            $updateData['board_type'] = $data['board_type'];
        }
        if (isset($data['port'])) {
            $updateData['serial_port'] = $data['port'];
        }

        $device->update($updateData);

        // Store last_state if provided
        if (isset($data['last_state'])) {
            $device->last_state = $data['last_state'];
            $device->save();
        }

        // Process logs batch if provided
        if (isset($data['logs']) && is_array($data['logs'])) {
            foreach ($data['logs'] as $logEntry) {
                if (isset($logEntry['message'])) {
                    \App\Models\ArduinoLog::create([
                        'device_id' => $device->id,
                        'message' => $logEntry['message'],
                        'level' => $logEntry['level'] ?? 'info',
                        'context' => $logEntry['context'] ?? null,
                    ]);
                }
            }
            Log::info('Logs batch processed', [
                'device_id' => $device->id,
                'count' => count($data['logs']),
            ]);
        }

        Log::debug('Agent heartbeat received', [
            'device_id' => $device->id,
            'has_state' => isset($data['last_state']),
            'has_logs' => isset($data['logs']),
        ]);

        return response()->json(['success' => true]);
    }

    public function pendingCommands(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $commands = $device->commands()
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'type', 'params']);

        return response()->json([
            'success' => true,
            'commands' => $commands,
        ]);
    }

    public function commandResult(Request $request, int $id): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $command = $device->commands()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,failed',
            'result_message' => 'nullable|string|max:1000',
            'output' => 'nullable|string',
            'error' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $resultData = [];
        if ($request->has('output')) {
            $resultData['output'] = $request->input('output');
        }
        if ($request->has('error')) {
            $resultData['error'] = $request->input('error');
        }

        $command->update([
            'status' => $request->input('status'),
            'result_message' => $request->input('result_message'),
            'result_data' => ! empty($resultData) ? $resultData : null,
            'completed_at' => now(),
        ]);

        Log::info('Command result received', [
            'command_id' => $command->id,
            'device_id' => $device->id,
            'status' => $request->input('status'),
            'message' => $request->input('result_message'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Queue Arduino compilation command
     */
    public function arduinoCompile(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'board' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $command = \App\Models\Command::create([
            'device_id' => $device->id,
            'type' => 'arduino_compile',
            'params' => [
                'code' => $request->input('code'),
                'board' => $request->input('board'),
            ],
            'status' => 'pending',
        ]);

        Log::info('Arduino compile command created', [
            'device_id' => $device->id,
            'command_id' => $command->id,
        ]);

        return response()->json([
            'success' => true,
            'command_id' => $command->id,
            'message' => 'Compile command queued',
        ]);
    }

    /**
     * Queue Arduino upload command
     */
    public function arduinoUpload(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'board' => 'required|string',
            'port' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $command = \App\Models\Command::create([
            'device_id' => $device->id,
            'type' => 'arduino_upload',
            'params' => [
                'code' => $request->input('code'),
                'board' => $request->input('board'),
                'port' => $request->input('port'),
            ],
            'status' => 'pending',
        ]);

        Log::info('Arduino upload command created', [
            'device_id' => $device->id,
            'command_id' => $command->id,
            'port' => $request->input('port'),
        ]);

        return response()->json([
            'success' => true,
            'command_id' => $command->id,
            'message' => 'Upload command queued',
        ]);
    }

    /**
     * Queue port scan command
     */
    public function scanPorts(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $command = \App\Models\Command::create([
            'device_id' => $device->id,
            'type' => 'scan_ports',
            'params' => [],
            'status' => 'pending',
        ]);

        Log::info('Port scan command created', [
            'device_id' => $device->id,
            'command_id' => $command->id,
        ]);

        return response()->json([
            'success' => true,
            'command_id' => $command->id,
            'message' => 'Port scan command queued',
        ]);
    }
}
