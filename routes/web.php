<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommandController;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;
use App\Http\Controllers\DeviceViewController;

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
});

// API command endpoints using session auth (web guard) to allow Blade console without Sanctum token
Route::middleware(['auth'])->prefix('api/growdash/devices')->group(function () {
    Route::post('/{device}/commands', [CommandController::class, 'send']);
    Route::get('/{device}/commands', [CommandController::class, 'history']);
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Device pairing page
    Volt::route('devices/pair', 'devices.pair')->name('devices.pair');
});
