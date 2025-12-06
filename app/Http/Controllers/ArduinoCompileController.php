<?php

namespace App\Http\Controllers;

use App\Models\DeviceScript;
use App\Models\Device;
use App\Models\Command;
use App\Services\ArduinoErrorAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ArduinoCompileController extends Controller
{
    /**
     * Send compile command to device agent
     */
    public function compile(Request $request, DeviceScript $script)
    {
        Log::debug('🔵 ArduinoCompileController::compile() aufgerufen', [
            'script_id' => $script->id,
            'script_name' => $script->name,
            'user_id' => Auth::id(),
            'request_data' => $request->all(),
        ]);

        if ($script->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'board' => 'nullable|string',
        ]);

        $device = Device::findOrFail($request->device_id);

        if ($device->user_id !== Auth::id()) {
            return response()->json(['error' => 'Device not owned by user'], 403);
        }

        $board = $request->input('board', 'esp32:esp32:esp32');

        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => Auth::id(),
            'type' => 'arduino_compile',
            'params' => [
                'script_id' => $script->id,
                'script_name' => $script->name,
                'code' => $script->code,
                'board' => $board,
            ],
            'status' => 'pending',
        ]);

        $script->update([
            'status' => 'compiling',
            'compile_log' => 'Kompilierung gestartet auf Device: ' . $device->name,
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@compile', [
            'user_id' => Auth::id(),
            'device_id' => $device->id,
            'script_id' => $script->id,
            'command_id' => $command->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compile-Befehl an Device gesendet',
            'command_id' => $command->id,
            'device' => $device->name,
            'script' => $script->fresh(),
        ]);
    }

    public function upload(Request $request, DeviceScript $script)
    {
        if ($script->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'port' => 'required|string',
            'board' => 'nullable|string',
            'target_device_id' => 'nullable|string',
        ]);

        $device = Device::findOrFail($request->device_id);

        if ($device->user_id !== Auth::id()) {
            return response()->json(['error' => 'Device not owned by user'], 403);
        }

        $port = $request->input('port');
        $board = $request->input('board', 'esp32:esp32:esp32');

        // Upload bedeutet: Compile + Upload in einem Schritt (arduino_compile_upload)
        // Der Agent kompiliert den Code frisch und uploaded ihn dann sofort
        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => Auth::id(),
            'type' => 'arduino_compile_upload',  // Nicht arduino_upload!
            'params' => [
                'script_id' => $script->id,
                'sketch_name' => $script->name,
                'code' => $script->code,
                'port' => $port,
                'board' => $board,
                'target_device_id' => $request->input('target_device_id'),
            ],
            'status' => 'pending',
        ]);

        $script->update([
            'status' => 'uploading',
            'flash_log' => 'Compile + Upload gestartet auf Device: ' . $device->name . ' → Port: ' . $port,
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@upload', [
            'user_id' => Auth::id(),
            'device_id' => $device->id,
            'script_id' => $script->id,
            'command_id' => $command->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compile + Upload-Befehl an Device gesendet',
            'command_id' => $command->id,
            'device' => $device->name,
            'script' => $script->fresh(),
        ]);
    }

    public function compileAndUpload(Request $request, DeviceScript $script)
    {
        if ($script->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'port' => 'required|string',
            'board' => 'nullable|string',
            'target_device_id' => 'nullable|string',
        ]);

        $device = Device::findOrFail($request->device_id);

        if ($device->user_id !== Auth::id()) {
            return response()->json(['error' => 'Device not owned by user'], 403);
        }

        $port = $request->input('port');
        $board = $request->input('board', 'esp32:esp32:esp32');

        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => Auth::id(),
            'type' => 'arduino_compile_upload',
            'params' => [
                'script_id' => $script->id,
                'script_name' => $script->name,
                'code' => $script->code,
                'port' => $port,
                'board' => $board,
                'target_device_id' => $request->input('target_device_id'),
            ],
            'status' => 'pending',
        ]);

        $script->update([
            'status' => 'compiling',
            'compile_log' => 'Compile & Upload gestartet auf Device: ' . $device->name,
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@compileAndUpload', [
            'user_id' => Auth::id(),
            'device_id' => $device->id,
            'script_id' => $script->id,
            'command_id' => $command->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compile & Upload-Befehl an Device gesendet',
            'command_id' => $command->id,
            'device' => $device->name,
            'script' => $script->fresh(),
        ]);
    }

    public function status(Request $request, DeviceScript $script)
    {
        if ($script->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@status', [
            'user_id' => Auth::id(),
            'script_id' => $script->id,
        ]);

        return response()->json([
            'script' => $script,
            'recent_commands' => Command::where('device_id', $script->device_id)
                ->whereIn('type', ['arduino_compile', 'arduino_upload', 'arduino_compile_upload'])
                ->where('params->script_id', $script->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Get status for multiple scripts at once (for polling)
     */
    public function statusMultiple(Request $request)
    {
        $scriptIds = $request->input('script_ids', []);
        if (!is_array($scriptIds) || empty($scriptIds)) {
            return response()->json(['scripts' => []]);
        }

        $scripts = DeviceScript::whereIn('id', $scriptIds)
            ->where('user_id', Auth::id())
            ->get(['id', 'status', 'name'])
            ->keyBy('id');

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@statusMultiple', [
            'user_id' => Auth::id(),
            'script_count' => $scripts->count(),
        ]);

        return response()->json(['scripts' => $scripts]);
    }

    public function listDevices()
    {
        $devices = Device::where('user_id', Auth::id())
            ->where('status', 'online')
            ->select('id', 'name', 'bootstrap_id', 'serial_port', 'device_info')
            ->get();

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@listDevices', [
            'user_id' => Auth::id(),
            'device_count' => $devices->count(),
        ]);

        return response()->json(['devices' => $devices]);
    }

    /**
     * Check command status and analyze errors with LLM if compilation failed
     */
    public function checkCommandStatus(Request $request, Command $command)
    {
        // Auth check
        if ($command->created_by_user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $response = [
            'command' => $command,
            'status' => $command->status,
        ];

        // If compilation failed, analyze error with LLM
        if ($command->status === 'failed' && $command->type === 'arduino_compile') {
            $errorMessage = $command->result_data['error'] ?? $command->result_data['output'] ?? 'Unbekannter Fehler';
            $originalCode = $command->params['code'] ?? '';
            $boardFqbn = $command->params['board'] ?? 'unknown';

            if ($errorMessage && $originalCode) {
                $analyzer = new ArduinoErrorAnalyzer();
                $analysis = $analyzer->analyzeAndFix($errorMessage, $originalCode, $boardFqbn);

                $response['error_analysis'] = $analysis;
                $response['original_error'] = $errorMessage;
            }
        }

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@checkCommandStatus', [
            'user_id' => Auth::id(),
            'command_id' => $command->id,
            'status' => $command->status,
        ]);

        return response()->json($response);
    }

    /**
     * Get available serial ports from device agent
     */
    public function getPorts(Device $device)
    {
        // Check ownership
        if ($device->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if device is online
        if ($device->status !== 'online') {
            return response()->json([
                'error' => 'Device offline',
                'ports' => []
            ], 400);
        }

        try {
            // Call agent's local API to get available ports
            // Use device's agent_url if set, otherwise fallback to APP_URL
            $agentUrl = $device->agent_url ?? config('app.url');

            $response = Http::timeout(5)->get("{$agentUrl}/ports");

            if ($response->successful()) {
                Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@getPorts', [
                    'user_id' => Auth::id(),
                    'device_id' => $device->id,
                    'success' => true,
                ]);

                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'Agent nicht erreichbar',
                'ports' => []
            ], 503);

        } catch (\Exception $e) {
            Log::error("Port-Scan fehlgeschlagen für Device {$device->id}: " . $e->getMessage());

            return response()->json([
                'error' => 'Port-Scan fehlgeschlagen: ' . $e->getMessage(),
                'ports' => []
            ], 500);
        }
    }
}
