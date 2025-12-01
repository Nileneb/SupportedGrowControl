<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test user
    $this->user = User::factory()->create();

    // Create test device
    $this->device = Device::create([
        'user_id' => $this->user->id,
        'name' => 'Test Growdash',
        'slug' => 'test-growdash',
        'ip_address' => '192.168.1.100',
        'serial_port' => '/dev/ttyUSB0',
    ]);

    $this->validToken = 'test-webhook-token';
    config(['services.growdash.webhook_token' => $this->validToken]);
});

test('webhook log endpoint requires token', function () {
    $response = $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'Test message',
    ]);

    $response->assertStatus(403);
});

test('webhook log endpoint accepts valid token', function () {
    $response = $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'Test message',
        'level' => 'info',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('arduino_logs', [
        'device_id' => $this->device->id,
        'message' => 'Test message',
        'level' => 'info',
    ]);
});

test('log endpoint creates device if not exists', function () {
    $response = $this->postJson('/api/growdash/log', [
        'device_slug' => 'new-device',
        'message' => 'Test message',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('devices', [
        'slug' => 'new-device',
        'name' => 'new-device',
    ]);
});

test('water level parsing from log message', function () {
    $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'WaterLevel: 75.5',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $this->assertDatabaseHas('water_levels', [
        'device_id' => $this->device->id,
        'level_percent' => 75.5,
    ]);

    $this->assertDatabaseHas('system_statuses', [
        'device_id' => $this->device->id,
        'water_level' => 75.5,
    ]);
});

test('tds parsing from log message', function () {
    $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'TDS: 450.2',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $this->assertDatabaseHas('tds_readings', [
        'device_id' => $this->device->id,
        'value_ppm' => 450.2,
    ]);

    $this->assertDatabaseHas('system_statuses', [
        'device_id' => $this->device->id,
        'last_tds' => 450.2,
    ]);
});

test('temperature parsing from log message', function () {
    $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'Temp: 22.5',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $this->assertDatabaseHas('temperature_readings', [
        'device_id' => $this->device->id,
        'value_c' => 22.5,
    ]);

    $this->assertDatabaseHas('system_statuses', [
        'device_id' => $this->device->id,
        'last_temperature' => 22.5,
    ]);
});

test('spray on parsing creates event', function () {
    $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'Spray: ON',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $this->assertDatabaseHas('spray_events', [
        'device_id' => $this->device->id,
        'manual' => false,
    ]);

    $this->assertDatabaseHas('system_statuses', [
        'device_id' => $this->device->id,
        'spray_active' => true,
    ]);
});

test('spray off parsing ends event', function () {
    // Start spray
    $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'Spray: ON',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    sleep(1); // Ensure time difference

    // Stop spray
    $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'Spray: OFF',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $event = $this->device->sprayEvents()->first();
    expect($event)->not->toBeNull();
    expect($event->end_time)->not->toBeNull();
    expect($event->duration_seconds)->toBeGreaterThan(0);

    $this->assertDatabaseHas('system_statuses', [
        'device_id' => $this->device->id,
        'spray_active' => false,
    ]);
});

test('filling on parsing creates event', function () {
    $this->postJson('/api/growdash/log', [
        'device_slug' => 'test-growdash',
        'message' => 'Filling: ON',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $this->assertDatabaseHas('fill_events', [
        'device_id' => $this->device->id,
        'manual' => false,
    ]);

    $this->assertDatabaseHas('system_statuses', [
        'device_id' => $this->device->id,
        'filling_active' => true,
    ]);
});

test('multiple log messages parse correctly', function () {
    $messages = [
        'WaterLevel: 80.0',
        'TDS: 500',
        'Temp: 23.5',
        'Spray: ON',
    ];

    foreach ($messages as $message) {
        $this->postJson('/api/growdash/log', [
            'device_slug' => 'test-growdash',
            'message' => $message,
        ], [
            'X-Growdash-Token' => $this->validToken,
        ]);
    }

    expect($this->device->waterLevels()->count())->toBe(1);
    expect($this->device->tdsReadings()->count())->toBe(1);
    expect($this->device->temperatureReadings()->count())->toBe(1);
    expect($this->device->sprayEvents()->count())->toBe(1);
});
