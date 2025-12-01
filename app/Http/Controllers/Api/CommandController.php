<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommandController extends Controller
{
    /**
     * Get pending commands for agent
     * GET /api/growdash/agent/commands/pending
     *
     * Returns commands with status 'pending' for this device
     */
    public function pending(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user('device');

        $commands = $device->commands()
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'type', 'params', 'created_at']);

        return response()->json([
            'success' => true,
            'commands' => $commands,
        ]);
    }

    /**
     * Submit command result from agent
     * POST /api/growdash/agent/commands/{id}/result
     *
     * Expected payload:
     * {
     *   "status": "completed",
     *   "result_message": "Spray completed successfully"
     * }
     */
    public function result(Request $request, int $id): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user('device');

        $command = Command::where('id', $id)
            ->where('device_id', $device->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:executing,completed,failed',
            'result_message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $command->update([
            'status' => $request->input('status'),
            'result_message' => $request->input('result_message'),
            'completed_at' => in_array($request->input('status'), ['completed', 'failed'])
                ? now()
                : null,
        ]);

        // TODO: Broadcast WebSocket event (CommandStatusUpdated)

        return response()->json([
            'success' => true,
            'message' => 'Command status updated',
            'command' => $command->only(['id', 'status', 'completed_at']),
        ]);
    }
}
