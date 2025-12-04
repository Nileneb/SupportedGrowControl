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
        $device = $request->device;

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
        $device = $request->device;

        $command = Command::where('id', $id)
            ->where('device_id', $device->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:executing,completed,failed',
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

        // Build result_data from request (includes error, output, etc.)
        $resultData = [];
        if ($request->has('error')) {
            $resultData['error'] = $request->input('error');
        }
        if ($request->has('output')) {
            $resultData['output'] = $request->input('output');
        }
        if ($request->has('stdout')) {
            $resultData['stdout'] = $request->input('stdout');
        }
        if ($request->has('stderr')) {
            $resultData['stderr'] = $request->input('stderr');
        }

        $command->update([
            'status' => $request->input('status'),
            'result_message' => $request->input('result_message'),
            'result_data' => !empty($resultData) ? $resultData : null,
            'completed_at' => in_array($request->input('status'), ['completed', 'failed'])
                ? now()
                : null,
        ]);

        Log::info('Command status updated', [
            'command_id' => $command->id,
            'device_id' => $device->id,
            'status' => $request->input('status'),
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

            // Special handling for interactive serial console commands
            if ($validated['type'] === 'serial_command') {
                $serialText = $validated['params']['command'] ?? null;
                $serialText = is_string($serialText) ? trim($serialText) : null;

                if (!$serialText) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Missing serial command text',
                        'errors' => ['params.command' => ['Required string']],
                    ], 422);
                }

                if (mb_strlen($serialText) > 256) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Serial command too long (max 256 chars)',
                        'errors' => ['params.command' => ['Too long']],
                    ], 422);
                }

                // Create command immediately and return (skip actuator capability validation)
                $command = Command::create([
                    'device_id' => $device->id,
                    'created_by_user_id' => Auth::id(),
                    'type' => 'serial_command',
                    'params' => ['command' => $serialText],
                    'status' => 'pending',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Serial command queued',
                    'command' => [
                        'id' => $command->id,
                        'type' => $command->type,
                        'params' => $command->params,
                        'status' => $command->status,
                        'created_at' => $command->created_at->toISOString(),
                    ]
                ], 201);
            }

            // Validate actuator-based commands against device capabilities (skip for serial_command above)
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

                    // Map actuator command to Arduino serial command
                    $arduinoCommand = $this->mapActuatorToArduinoCommand($validated['type'], $providedParams);

                    // Create serial_command instead of actuator-specific type
                    $command = Command::create([
                        'device_id' => $device->id,
                        'created_by_user_id' => Auth::id(),
                        'type' => 'serial_command',
                        'params' => ['command' => $arduinoCommand],
                        'status' => 'pending',
                    ]);

                    Log::info('Actuator command mapped to serial', [
                        'actuator_type' => $validated['type'],
                        'arduino_command' => $arduinoCommand,
                        'command_id' => $command->id,
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
    private function mapActuatorToArduinoCommand(string $actuatorType, array $params): string
    {
        return match($actuatorType) {
            'spray_pump' => $this->buildSprayCommand($params),
            'fill_valve' => $this->buildFillCommand($params),
            'pump' => $this->buildPumpCommand($params),
            'valve' => $this->buildValveCommand($params),
            'light' => $this->buildLightCommand($params),
            'fan' => $this->buildFanCommand($params),
            default => "STATUS", // Fallback
        };
    }

    private function buildSprayCommand(array $params): string
    {
        $durationMs = $params['duration_ms'] ?? 1000;
        return "Spray {$durationMs}";
    }

    private function buildFillCommand(array $params): string
    {
        // Check if we have duration_ms (time-based) or target_liters (volume-based)
        if (isset($params['target_liters'])) {
            $liters = $params['target_liters'];
            return "FillL {$liters}";
        }

        // Duration-based fill (convert ms to seconds, use as rough estimate)
        $durationMs = $params['duration_ms'] ?? 5000;
        $durationSec = $durationMs / 1000;
        $estimatedLiters = ($durationSec / 60) * 6.0; // Assume 6L/min fill rate
        return "FillL " . number_format($estimatedLiters, 2);
    }

    private function buildPumpCommand(array $params): string
    {
        // Generic pump command
        $durationMs = $params['duration_ms'] ?? 1000;
        return "Spray {$durationMs}"; // Re-use spray pin for generic pump
    }

    private function buildValveCommand(array $params): string
    {
        $state = $params['state'] ?? 'on';
        return $state === 'on' ? "TabON" : "TabOFF";
    }

    private function buildLightCommand(array $params): string
    {
        // Assuming custom light control command
        $state = $params['state'] ?? 'on';
        return $state === 'on' ? "LightON" : "LightOFF";
    }

    private function buildFanCommand(array $params): string
    {
        $durationMs = $params['duration_ms'] ?? 5000;
        return "Fan {$durationMs}";
    }
}
