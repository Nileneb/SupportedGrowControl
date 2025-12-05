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

        $validator = Validator::make($request->all(), [
            'ip_address' => 'nullable|ip',
            'api_port' => 'nullable|integer|between:1,65535',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [
            'last_seen_at' => now(),
            'status' => 'online',
        ];

        if ($request->has('ip_address')) {
            $updateData['ip_address'] = $request->string('ip_address');
        }
        if ($request->has('api_port')) {
            $updateData['api_port'] = $request->integer('api_port');
        }

        $device->update($updateData);

        Log::info('Agent heartbeat received', [
            'device_id' => $device->id,
            'ip_address' => $request->input('ip_address'),
            'api_port' => $request->input('api_port'),
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

}
