<?php

namespace App\Http\Controllers;

use App\Models\DeviceScript;
use App\Models\Device;
use App\Models\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ArduinoCompileController extends Controller
{
    /**
     * Send compile command to device agent
     */
    public function compile(Request $request, DeviceScript $script)
    {
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

        if ($script->status !== 'compiled') {
            return response()->json(['error' => 'Script must be compiled first'], 400);
        }

        $port = $request->input('port');
        $board = $request->input('board', 'esp32:esp32:esp32');

        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => Auth::id(),
            'type' => 'arduino_upload',
            'params' => [
                'script_id' => $script->id,
                'script_name' => $script->name,
                'port' => $port,
                'board' => $board,
                'target_device_id' => $request->input('target_device_id'),
            ],
            'status' => 'pending',
        ]);

        $script->update([
            'status' => 'uploading',
            'flash_log' => 'Upload gestartet auf Device: ' . $device->name . ' → Port: ' . $port,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Upload-Befehl an Device gesendet',
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

    public function listDevices()
    {
        $devices = Device::where('user_id', Auth::id())
            ->where('status', 'online')
            ->select('id', 'name', 'bootstrap_id', 'device_info')
            ->get();

        return response()->json(['devices' => $devices]);
    }
}
