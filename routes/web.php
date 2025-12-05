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

    // Sensor/Actuator management wizards
    Route::get('/{device}/sensors/add', App\Livewire\Devices\AddSensor::class)->name('devices.sensors.add');
    Route::get('/{device}/actuators/add', App\Livewire\Devices\AddActuator::class)->name('devices.actuators.add');
});

// API command endpoints using session auth (web guard) to allow Blade console without Sanctum token
Route::middleware(['auth'])->prefix('api/growdash/devices')->group(function () {
    Route::post('/{device}/commands', [CommandController::class, 'send']);
    Route::get('/{device}/commands', [CommandController::class, 'history']);
});

// Arduino CLI API endpoints (commands sent to device agents)
Route::middleware(['auth'])->prefix('api/arduino')->group(function () {
    Route::get('/devices', [\App\Http\Controllers\ArduinoCompileController::class, 'listDevices']);
    Route::get('/devices/{device}/ports', [\App\Http\Controllers\ArduinoCompileController::class, 'getPorts']);
    Route::get('/scripts/{script}/status', [\App\Http\Controllers\ArduinoCompileController::class, 'status']);
    Route::get('/commands/{command}/status', [\App\Http\Controllers\ArduinoCompileController::class, 'checkCommandStatus']);
    Route::post('/scripts/{script}/compile', [\App\Http\Controllers\ArduinoCompileController::class, 'compile']);
    Route::post('/scripts/{script}/upload', [\App\Http\Controllers\ArduinoCompileController::class, 'upload']);
    Route::post('/scripts/{script}/compile-upload', [\App\Http\Controllers\ArduinoCompileController::class, 'compileAndUpload']);
});

Route::middleware(['auth'])->group(function () {
    // Feedback submission
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::view('/feedback', 'feedback.form')->name('feedback.form');
});

// Admin routes
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
});
