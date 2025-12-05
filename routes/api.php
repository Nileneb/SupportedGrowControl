<?php

use App\Http\Controllers\BootstrapController;
use App\Http\Controllers\DevicePairingController;
use App\Http\Controllers\GrowdashWebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DeviceRegistrationController;
use App\Http\Controllers\Api\ShellyWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Growdash API Routes
|--------------------------------------------------------------------------
|
| These routes handle webhook callbacks from Growdash devices and provide
| public API endpoints for retrieving device status and historical data.
|
*/

// ==================== Auth & Device Registration ====================

// API login (Sanctum token issuance)
Route::post('/auth/login', [AuthController::class, 'login']);

// API logout (token revocation)
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

// Direct device registration from an authenticated agent (alternative to pairing flow)
Route::middleware('auth:sanctum')->post('/growdash/devices/register-from-agent', [DeviceRegistrationController::class, 'registerFromAgent']);
// Alias für Agent-Kompatibilität (Direct-Login-Flow)
Route::middleware('auth:sanctum')->prefix('growdash')->group(function () {
    Route::post('/devices/register', [DeviceController::class, 'register']);
});

// ==================== Bootstrap & Pairing ====================

// Public bootstrap endpoint for agents (no auth required)
Route::post('/agents/bootstrap', [BootstrapController::class, 'bootstrap']);

// Pairing status polling endpoint (agent checks if user paired)
Route::get('/agents/pairing/status', [BootstrapController::class, 'status']);

// Device pairing endpoints (require user authentication)
Route::middleware('auth:web')->prefix('devices')->group(function () {
    Route::post('/pair', [DevicePairingController::class, 'pair']);
    Route::get('/unclaimed', [DevicePairingController::class, 'unclaimed']);
});

// ==================== Agent API (Device-Authenticated) ====================

// Agent endpoints protected by device token (X-Device-ID + X-Device-Token)
Route::middleware('device.auth')->prefix('growdash/agent')->group(function () {
    // POST telemetry data (sensor readings)
    Route::post('/telemetry', [\App\Http\Controllers\Api\TelemetryController::class, 'store']);

    // GET pending commands for this device
    Route::get('/commands/pending', [\App\Http\Controllers\Api\CommandController::class, 'pending']);

    // POST command result/acknowledgement
    Route::post('/commands/{id}/result', [\App\Http\Controllers\Api\CommandController::class, 'result']);

    // POST/PUT device capabilities (what sensors/actuators are available)
    Route::post('/capabilities', [\App\Http\Controllers\Api\DeviceManagementController::class, 'updateCapabilities']);

    // GET device capabilities in agent-ready flat format
    Route::get('/capabilities', [\App\Http\Controllers\Api\DeviceManagementController::class, 'getCapabilities']);

    // POST device logs
    Route::post('/logs', [\App\Http\Controllers\Api\LogController::class, 'store']);

    // POST heartbeat/last_seen update
    Route::post('/heartbeat', [\App\Http\Controllers\Api\DeviceManagementController::class, 'heartbeat']);

    // GET available serial ports (proxied to agent's local API)
    Route::get('/ports', [\App\Http\Controllers\Api\AgentController::class, 'getPorts']);
});

// ==================== User API (Sanctum-Authenticated) ====================

// Get user's devices with credentials for testing/agent management
Route::middleware('auth:sanctum')->get('/user/devices', function (Request $request) {
    $devices = $request->user()->devices()
        ->select('id', 'public_id', 'name', 'status', 'last_seen_at', 'capabilities', 'board_type')
        ->get()
        ->map(function ($device) {
            return [
                'id' => $device->id,
                'public_id' => $device->public_id,
                'name' => $device->name,
                'status' => $device->status,
                'last_seen_at' => $device->last_seen_at,
                'board_type' => $device->board_type,
                'capabilities' => $device->capabilities,
            ];
        });

    return response()->json([
        'success' => true,
        'devices' => $devices,
        'count' => $devices->count(),
    ]);
});

// Delete a device (removes all associated data)
Route::middleware('auth:sanctum')->delete('/user/devices/{device}', function (Request $request, \App\Models\Device $device) {
    // Verify device belongs to authenticated user
    if ($device->user_id !== $request->user()->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $deviceName = $device->name;
    $device->delete(); // Cascading deletes will remove related data

    return response()->json([
        'success' => true,
        'message' => "Device '{$deviceName}' deleted successfully",
    ]);
});

// Regenerate agent token for testing (returns NEW plaintext token)
Route::middleware('auth:sanctum')->post('/user/devices/{device}/regenerate-token', function (Request $request, \App\Models\Device $device) {
    // Verify device belongs to authenticated user
    if ($device->user_id !== $request->user()->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Generate new token
    $plaintextToken = \Illuminate\Support\Str::random(64);
    $device->agent_token = hash('sha256', $plaintextToken);
    $device->save();

    return response()->json([
        'success' => true,
        'message' => 'Agent token regenerated successfully',
        'public_id' => $device->public_id,
        'agent_token' => $plaintextToken,
        'warning' => 'Store this token securely - it will not be shown again!',
    ]);
});

// Send command to device (web UI → device)
Route::post('/growdash/devices/{device}/commands', [\App\Http\Controllers\Api\CommandController::class, 'send'])
    ->middleware('auth:sanctum')
    ->name('api.devices.commands.create');

// Refresh device capabilities (trigger agent to send updated capabilities)
Route::post('/devices/{device}/refresh-capabilities', function (Request $request, \App\Models\Device $device) {
    // Verify ownership
    if ($device->user_id !== Auth::id()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    // Create a command for the agent to refresh capabilities
    \App\Models\Command::create([
        'device_id' => $device->id,
        'created_by_user_id' => Auth::id(),
        'type' => 'refresh_capabilities',
        'params' => [],
        'status' => 'pending',
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Capability refresh requested'
    ]);
})->middleware('auth:sanctum');

// ==================== Legacy Webhook Endpoints ====================

// Shelly device webhooks (public endpoint with token authentication)
Route::post('/shelly/webhook/{shelly}', [ShellyWebhookController::class, 'handle'])
    ->name('api.shelly.webhook');

// Protected webhook endpoints (require X-Growdash-Token header)
Route::middleware('growdash.webhook')->prefix('growdash')->group(function () {
    // Webhook: Receive log messages from devices
    Route::post('/log', [GrowdashWebhookController::class, 'log']);

    // Webhook: Receive structured events from devices (optional)
    Route::post('/event', [GrowdashWebhookController::class, 'event']);

    // Manual control endpoints (require authentication)
    Route::post('/manual-spray', [GrowdashWebhookController::class, 'manualSpray']);
    Route::post('/manual-fill', [GrowdashWebhookController::class, 'manualFill']);
});

// Public API endpoints (require authentication for data access)
Route::prefix('growdash')->middleware('auth:web')->group(function () {
    // Current system status
    Route::get('/status', [GrowdashWebhookController::class, 'status']);

    // Historical data endpoints
    Route::get('/water-history', [GrowdashWebhookController::class, 'waterHistory']);
    Route::get('/tds-history', [GrowdashWebhookController::class, 'tdsHistory']);
    Route::get('/temperature-history', [GrowdashWebhookController::class, 'temperatureHistory']);

    // Event histories
    Route::get('/spray-events', [GrowdashWebhookController::class, 'sprayEvents']);
    Route::get('/fill-events', [GrowdashWebhookController::class, 'fillEvents']);

    // Arduino logs
    Route::get('/logs', [GrowdashWebhookController::class, 'logs']);
});
