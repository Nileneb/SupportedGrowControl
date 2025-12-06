<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommandController;
use Livewire\Volt\Volt;

// Welcome & home
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authenticated dashboard
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Device management - CORE ONLY
Route::middleware(['auth', 'verified'])->prefix('devices')->group(function () {
    Route::get('/', App\Livewire\Devices\Index::class)->name('devices.index');
    Route::get('/pair', App\Livewire\DevicePairing::class)->name('devices.pair');
});

// API command endpoints - CORE AGENT API
Route::middleware(['auth'])->prefix('api/growdash/devices')->group(function () {
    Route::post('/{device}/commands', [CommandController::class, 'send']);
    Route::get('/{device}/commands', [CommandController::class, 'history']);
});
