<?php

namespace App\Http\Controllers\Api;

use App\DTOs\DeviceCapabilities;
use App\Events\CommandStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        // Broadcast WebSocket event
        broadcast(new CommandStatusUpdated($command));

        return response()->json([
            'success' => true,
            'message' => 'Command status updated',
            'command' => $command->only(['id', 'status', 'completed_at']),
        ]);
    }

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

            // Validate command type and params against device capabilities
            if ($device->capabilities) {
                try {
                    $capabilitiesDTO = DeviceCapabilities::fromArray($device->capabilities);
                    $actuator = $capabilitiesDTO->getActuatorById($validated['type']);

                    if (!$actuator) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unknown actuator: {$validated['type']}",
                            'available_actuators' => array_map(
                                fn($a) => $a->id,
                                $capabilitiesDTO->actuators
                            ),
                        ], 422);
                    }

                    // Validate params against actuator spec
                    $providedParams = $validated['params'] ?? [];
                    $paramErrors = $actuator->validateParams($providedParams);

                    if (!empty($paramErrors)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid command parameters',
                            'errors' => $paramErrors,
                        ], 422);
                    }
                } catch (\Exception $e) {
                    // If capabilities validation fails, continue without it
                    Log::warning('Failed to validate command against capabilities', [
                        'device_id' => $device->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Create command
            $command = Command::create([
                'device_id' => $device->id,
                'created_by_user_id' => Auth::id(),
                'type' => $validated['type'],
                'params' => $validated['params'] ?? [],
                'status' => 'pending',
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
}
