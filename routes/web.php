<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\FeedbackController;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
// Removed DevicePairingController as it is no longer used

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('devices')->group(function () {
    Route::get('/', App\Livewire\Devices\Index::class)->name('devices.index');
    Volt::route('/pair', 'devices.pair')->name('devices.pair');
    Route::get('/{device}', [DeviceViewController::class, 'show'])->name('devices.show');

    // Sensor/Actuator management wizards
    Route::get('/{device}/sensors/add', App\Livewire\Devices\AddSensor::class)->name('devices.sensors.add');
    Route::get('/{device}/actuators/add', App\Livewire\Devices\AddActuator::class)->name('devices.actuators.add');
});

// API command endpoints using session auth (web guard) to allow Blade console without Sanctum token
Route::middleware(['web', 'auth'])->prefix('api/growdash/devices')->group(function () {
    Route::post('/{device}/commands', [CommandController::class, 'send']);
    Route::get('/{device}/commands', [CommandController::class, 'history']);
});

// Arduino CLI API endpoints (commands sent to device agents)
Route::middleware(['web', 'auth'])->prefix('api/arduino')->group(function () {
    Route::get('/devices', [\App\Http\Controllers\ArduinoCompileController::class, 'listDevices']);
    Route::get('/devices/{device}/ports', [\App\Http\Controllers\ArduinoCompileController::class, 'getPorts']);
    Route::get('/scripts/{script}/status', [\App\Http\Controllers\ArduinoCompileController::class, 'status']);
    Route::get('/scripts/status/batch', [\App\Http\Controllers\ArduinoCompileController::class, 'statusMultiple']);
    Route::get('/commands/{command}/status', [\App\Http\Controllers\ArduinoCompileController::class, 'checkCommandStatus']);
    Route::post('/scripts/{script}/compile', [\App\Http\Controllers\ArduinoCompileController::class, 'compile']);
    Route::post('/scripts/{script}/upload', [\App\Http\Controllers\ArduinoCompileController::class, 'upload']);
    Route::post('/scripts/{script}/compile-upload', [\App\Http\Controllers\ArduinoCompileController::class, 'compileAndUpload']);
});

Route::middleware(['auth'])->group(function () {
    // Feedback submission
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::view('/feedback', 'feedback.form')->name('feedback.form');

    // Settings/Profile routes
    Volt::route('/profile', 'profile.edit')->name('profile.edit');
    Volt::route('/password', 'user-password.edit')->name('user-password.edit');
    Volt::route('/appearance', 'appearance.edit')->name('appearance.edit');
    Volt::route('/two-factor', 'two-factor.show')->name('two-factor.show');

    // Shelly devices
    Volt::route('/shelly', 'shelly.index')->name('shelly.index');
});// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/feedback', App\Livewire\Admin\FeedbackList::class)->name('admin.feedback');
    Route::get('/users', App\Livewire\Admin\UserManagement::class)->name('admin.users');
    Route::get('/webcams', App\Livewire\Admin\WebcamManagement::class)->name('admin.webcams');
    Route::get('/scripts', App\Livewire\Admin\DeviceScriptManagement::class)->name('admin.scripts');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('admin.feedback.store');
});

// Calendar routes
Route::middleware(['auth'])->group(function () {
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [\App\Http\Controllers\CalendarController::class, 'events'])->name('calendar.events');
    Route::get('/devices/{device}/logs-data', [\App\Http\Controllers\Api\DeviceLogsController::class, 'index'])
        ->withoutMiddleware(['auth'])
        ->name('devices.logs.data');
    
    // System Logs Viewer (Zentral mit Arduino-Filter)
    Route::get('/logs', [\App\Http\Controllers\LogsController::class, 'index'])->name('logs.index');
    Route::get('/logs/all', [\App\Http\Controllers\LogsController::class, 'all'])->name('logs.all');
    Route::delete('/logs/clear', [\App\Http\Controllers\LogsController::class, 'clear'])->name('logs.clear');

    // Device Commands API for Calendar
    Route::get('/devices/{device}/commands', [\App\Http\Controllers\DeviceActuatorController::class, 'getCommands'])->name('devices.commands');
});
