<?php

namespace App\Http\Controllers;

use App\Models\Command;
use App\Models\Device;
use App\Models\DeviceScript;
use App\Services\ArduinoErrorAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $board = $request->input('board', 'arduino:avr:uno');

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
            'compile_log' => 'Kompilierung gestartet auf Device: '.$device->name,
        ]);

        Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@compile', [
            'user_id' => Auth::id(),
            'device_id' => $device->id,
            'script_id' => $script->id,
            'command_id' => $command->id,
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

        // Port optional: fallback auf zuletzt bekannten Port oder gespeicherten serial_port
        $port = $request->input('port')
            ?? ($device->last_state['port'] ?? null)
            ?? ($device->serial_port ?? null)
            ?? ($device->device_info['port'] ?? null);
        if (!$port) {
            return response()->json(['error' => 'Kein Port angegeben oder gefunden'], 422);
        }

        $port = $request->input('port');
        $board = $request->input('board', 'esp32:esp32:esp32');

        // Upload bedeutet: Compile + Upload in einem Schritt (arduino_compile_upload)
        // Der Agent kompiliert den Code frisch und uploaded ihn dann sofort
        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => Auth::id(),
            'type' => 'arduino_upload',
            'params' => [
                'script_id' => $script->id,
                'script_name' => $script->name,
                'code' => $script->code,  // Agent needs code to compile+upload
                'port' => $port,
                'board' => $board,
                'target_device_id' => $request->input('target_device_id'),
            ],
            'status' => 'pending',
        ]);

        $logMessage = $port
            ? "Upload gestartet auf Device: {$device->name} → Port: {$port}"
            : "Upload gestartet auf Device: {$device->name} (Port aus Board-Registry)";

        $script->update([
            'status' => 'uploading',
            'flash_log' => 'Upload gestartet auf Device: ' . $device->name . ' → Port: ' . $port,
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
            'port' => 'nullable|string', // Optional: Agent nutzt Board-Registry falls leer
            'board' => 'nullable|string',
            'target_device_id' => 'nullable|string',
        ]);

        $device = Device::findOrFail($request->device_id);

        if ($device->user_id !== Auth::id()) {
            return response()->json(['error' => 'Device not owned by user'], 403);
        }

        $port = $request->input('port'); // Kann null sein
        $board = $request->input('board', 'arduino:avr:uno');

        $params = [
            'script_id' => $script->id,
            'script_name' => $script->name,
            'code' => $script->code,
            'board' => $board,
            'target_device_id' => $request->input('target_device_id'),
        ];

        // Port nur hinzufügen wenn explizit angegeben
        if ($port) {
            $params['port'] = $port;
        }

        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => Auth::id(),
            'type' => 'arduino_compile_upload',
            'params' => $params,
            'status' => 'pending',
        ]);

        $script->update([
            'status' => 'compiling',
            'compile_log' => 'Compile & Upload gestartet auf Device: '.$device->name,
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
            ->select('id', 'name', 'bootstrap_id', 'device_info')
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

        // If upload succeeded, update script status
        if ($command->status === 'completed' && $command->type === 'arduino_upload') {
            $scriptId = $command->params['script_id'] ?? null;
            if ($scriptId) {
                $script = DeviceScript::find($scriptId);
                if ($script && $script->user_id === Auth::id()) {
                    $script->update([
                        'status' => 'flashed',
                        'flash_log' => 'Firmware erfolgreich auf Zielgerät geflasht!',
                    ]);
                }
            }
        }

        // If compilation succeeded, update script status
        if ($command->status === 'completed' && $command->type === 'arduino_compile') {
            $scriptId = $command->params['script_id'] ?? null;
            if ($scriptId) {
                $script = DeviceScript::find($scriptId);
                if ($script && $script->user_id === Auth::id()) {
                    $script->update([
                        'status' => 'compiled',
                        'compile_log' => 'Kompilierung erfolgreich abgeschlossen!',
                    ]);
                }
            }
        }

        // If compilation failed, analyze error with LLM
        if ($command->status === 'failed' && $command->type === 'arduino_compile') {
            $errorMessage = $command->result_data['error'] ?? $command->result_data['output'] ?? 'Unbekannter Fehler';
            $originalCode = $command->params['code'] ?? '';
            $boardFqbn = $command->params['board'] ?? 'unknown';

            if ($errorMessage && $originalCode) {
                $analyzer = new ArduinoErrorAnalyzer;
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

        // Check if device has IP address
        if (! $device->ip_address) {
            // Fallback: return common ports if device has no IP
            return response()->json([
                'success' => true,
                'ports' => [
                    ['port' => '/dev/ttyACM0', 'description' => 'Arduino Uno', 'manufacturer' => 'Arduino'],
                    ['port' => '/dev/ttyUSB0', 'description' => 'USB-Serial', 'manufacturer' => 'FTDI'],
                    ['port' => 'COM3', 'description' => 'COM3 (Windows)', 'manufacturer' => 'Standard'],
                    ['port' => 'COM4', 'description' => 'COM4 (Windows)', 'manufacturer' => 'Standard'],
                ],
                'count' => 4,
                'fallback' => true,
            ]);
        }

        try {
            // Call agent's local API to get available ports
            $response = Http::timeout(10)->get("http://{$device->ip_address}:8000/ports");

            if ($response->successful()) {
                Log::info('🎯 ENDPOINT_TRACKED: ArduinoCompileController@getPorts', [
                    'user_id' => Auth::id(),
                    'device_id' => $device->id,
                    'success' => true,
                ]);

                return response()->json($response->json());
            }

            // Agent unreachable - return fallback
            return response()->json([
                'success' => true,
                'ports' => [
                    ['port' => '/dev/ttyACM0', 'description' => 'Arduino Uno (Fallback)', 'manufacturer' => 'Arduino'],
                    ['port' => '/dev/ttyUSB0', 'description' => 'USB-Serial (Fallback)', 'manufacturer' => 'FTDI'],
                ],
                'count' => 2,
                'fallback' => true,
            ]);

        } catch (\Exception $e) {
            Log::error("Port-Scan failed for Device {$device->id}: ".$e->getMessage());

            // Return fallback ports
            return response()->json([
                'success' => true,
                'ports' => [
                    ['port' => '/dev/ttyACM0', 'description' => 'Arduino Uno (Error)', 'manufacturer' => 'Arduino'],
                    ['port' => '/dev/ttyUSB0', 'description' => 'USB-Serial (Error)', 'manufacturer' => 'FTDI'],
                ],
                'count' => 2,
                'fallback' => true,
            ]);
        }
    }
}
