<?php

use App\Http\Controllers\GrowdashWebhookController;
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
