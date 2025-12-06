<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DeviceRegistrationController;
use App\Http\Controllers\BootstrapController;
use App\Http\Controllers\GrowdashApiController;
use App\Http\Controllers\GrowdashManualController;
use App\Http\Controllers\GrowdashWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * GROWDASH API ROUTES (CLEAN ARCHITECTURE)
 *
 * Three authentication strategies:
 * 1. PUBLIC (no auth) - Agent bootstrap & pairing
 * 2. SANCTUM (auth:sanctum) - User authentication for web/app
 * 3. DEVICE (X-Device-ID + X-Device-Token) - Agent device communication
 * 4. WEBHOOK (X-Growdash-Token) - Webhook authentication for Arduino logs
 */

// ============================================================================
// PUBLIC ENDPOINTS - No Authentication Required
// ============================================================================

// Agent Bootstrap: Device gets initial configuration
Route::post('/agents/bootstrap', [BootstrapController::class, 'bootstrap']);

// Pairing Status: Agent checks if user has paired it
Route::get('/agents/pairing/status', [BootstrapController::class, 'status']);

// ============================================================================
// WEBHOOK AUTHENTICATION - Arduino/Growdash Logs (X-Growdash-Token header)
// ============================================================================

Route::post('/growdash/log', [GrowdashWebhookController::class, 'log']);
Route::post('/growdash/manual-spray', [GrowdashManualController::class, 'manualSpray']);
Route::post('/growdash/manual-fill', [GrowdashManualController::class, 'manualFill']);

// Shelly Webhook: Receive status updates from Shelly devices
Route::post('/shelly/webhook/{shelly}', [\App\Http\Controllers\ShellyWebhookController::class, 'handleWebhook'])
    ->name('api.shelly.webhook');

// ============================================================================
// USER AUTHENTICATION - Sanctum (API tokens for web/mobile apps)
// ============================================================================

Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/devices/register', [DeviceRegistrationController::class, 'registerFromAgent']);

// User pairs device with bootstrap code
Route::middleware('auth:sanctum')->post('/devices/pair', [BootstrapController::class, 'pair']);

// Send command to device (web → device)
Route::post('/growdash/devices/{device}/commands', [CommandController::class, 'send'])
    ->middleware('auth:sanctum')
    ->name('api.devices.commands.create');

// Growdash data endpoints (user authenticated)
Route::middleware('auth:sanctum')->prefix('growdash')->group(function () {
    Route::get('/status', [GrowdashApiController::class, 'status']);
    Route::get('/water-history', [GrowdashApiController::class, 'waterHistory']);
    Route::get('/tds-history', [GrowdashApiController::class, 'tdsHistory']);
    Route::get('/temperature-history', [GrowdashApiController::class, 'temperatureHistory']);
    Route::get('/spray-events', [GrowdashApiController::class, 'sprayEvents']);
    Route::get('/fill-events', [GrowdashApiController::class, 'fillEvents']);
    Route::get('/logs', [GrowdashApiController::class, 'logs']);
});

// ============================================================================
// DEVICE AUTHENTICATION - Agent API (X-Device-ID + X-Device-Token headers)
// ============================================================================

Route::middleware(\App\Http\Middleware\AuthenticateDevice::class)->prefix('growdash/agent')->group(function () {
    // Heartbeat: Device tells server it's alive
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);

    // Commands: Agent polls for pending commands
    Route::get('/commands/pending', [AgentController::class, 'pendingCommands']);

    // Command Result: Agent reports command execution result
    Route::post('/commands/{id}/result', [AgentController::class, 'commandResult']);

    // Arduino Compilation: Compile script on device
    Route::post('/arduino/compile', [AgentController::class, 'arduinoCompile']);

    // Arduino Upload: Upload compiled firmware to device
    Route::post('/arduino/upload', [AgentController::class, 'arduinoUpload']);

    // Port Scanner: Get available serial ports
    Route::get('/ports/scan', [AgentController::class, 'scanPorts']);
});
