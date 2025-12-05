<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommandController extends Controller
{
    /**
     * Send a new command to device (called by authenticated user from frontend)
     * POST /api/growdash/devices/{device}/commands
     */
    public function send(Request $request, string $devicePublicId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|max:50',
                'params' => 'nullable|array',
            ]);

            // Find device and verify ownership
            $device = Device::where('public_id', $devicePublicId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Check if device is online
            if ($device->status !== 'online') {
                return response()->json([
                    'success' => false,
                    'message' => 'Device is not online',
                    'device_status' => $device->status,
                ], 400);
            }
            // Create command
            $command = Command::create([
                'device_id' => $device->id,
                'created_by_user_id' => Auth::id(),
                'type' => $validated['type'],
                'params' => $validated['params'] ?? [],
                'status' => 'pending',
            ]);

            Log::info('Command created', [
                'command_id' => $command->id,
                'device_id' => $device->id,
                'type' => $command->type,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Command queued successfully',
                'command' => [
                    'id' => $command->id,
                    'type' => $command->type,
                    'params' => $command->params,
                    'status' => $command->status,
                    'created_at' => $command->created_at->toISOString(),
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found or access denied'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to send command to device', [
                'device_public_id' => $devicePublicId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send command'
            ], 500);
        }
    }

    /**
     * Get command history for a device (called by authenticated user)
     * GET /api/growdash/devices/{device}/commands
     */
    public function history(Request $request, string $devicePublicId): JsonResponse
    {
        try {
            // Find device and verify ownership
            $device = Device::where('public_id', $devicePublicId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $limit = $request->input('limit', 50);
            $limit = min($limit, 100); // Max 100

            $commands = Command::where('device_id', $device->id)
                ->with('createdBy:id,name')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($command) {
                    return [
                        'id' => $command->id,
                        'type' => $command->type,
                        'params' => $command->params,
                        'status' => $command->status,
                        'result_message' => $command->result_message,
                        'created_by' => $command->createdBy?->name,
                        'created_at' => $command->created_at->toISOString(),
                        'completed_at' => $command->completed_at?->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'commands' => $commands,
                'count' => $commands->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get command history', [
                'device_public_id' => $devicePublicId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve command history'
            ], 500);
        }
    }

    /**
     * Map actuator commands to Arduino serial commands
     * Based on GrowDash agent expectations (see https://github.com/nileneb/growdash)
     */
}
