<?php

use App\Http\Controllers\BootstrapController;
use App\Http\Controllers\DevicePairingController;
use App\Http\Controllers\GrowdashWebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceRegistrationController;
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

// Direct device registration from an authenticated agent (alternative to pairing flow)
Route::middleware('auth:sanctum')->post('/growdash/devices/register-from-agent', [DeviceRegistrationController::class, 'registerFromAgent']);

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

    // POST device logs
    Route::post('/logs', [\App\Http\Controllers\Api\LogController::class, 'store']);

    // POST heartbeat/last_seen update
    Route::post('/heartbeat', [\App\Http\Controllers\Api\DeviceManagementController::class, 'heartbeat']);
});

// ==================== Legacy Webhook Endpoints ====================

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
