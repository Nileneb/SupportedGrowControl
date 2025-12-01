<?php

use App\Models\Device;
use App\Models\User;
use App\Models\WaterLevel;
use App\Models\TdsReading;
use App\Models\TemperatureReading;
use App\Models\SprayEvent;
use App\Models\FillEvent;
use App\Models\ArduinoLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    
    $this->device = Device::create([
        'user_id' => $this->user->id,
        'name' => 'Test Growdash',
        'slug' => 'test-growdash',
        'ip_address' => '192.168.1.100',
        'serial_port' => '/dev/ttyUSB0',
    ]);
});

test('status endpoint requires authentication', function () {
    $response = $this->getJson('/api/growdash/status?device_slug=test-growdash');
    $response->assertStatus(401);
});

test('status endpoint denies access to other users devices', function () {
    $otherUser = User::factory()->create();
    
    $response = $this->actingAs($otherUser)
        ->getJson('/api/growdash/status?device_slug=test-growdash');
    
    $response->assertStatus(403);
});

test('status endpoint returns default values for device without data', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/status?device_slug=test-growdash');

    $response->assertStatus(200)
        ->assertJson([
            'water_level' => 0,
            'water_liters' => 0,
            'spray_active' => false,
            'filling_active' => false,
            'last_tds' => null,
            'last_temperature' => null,
        ]);
});

test('status endpoint returns actual system status', function () {
    // Create system status
    $this->device->systemStatuses()->create([
        'measured_at' => now(),
        'water_level' => 75.5,
        'water_liters' => 15.2,
        'spray_active' => true,
        'filling_active' => false,
        'last_tds' => 450.2,
        'last_temperature' => 22.5,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/status?device_slug=test-growdash');

    $response->assertStatus(200)
        ->assertJson([
            'water_level' => 75.5,
            'water_liters' => 15.2,
            'spray_active' => true,
            'filling_active' => false,
            'last_tds' => 450.2,
            'last_temperature' => 22.5,
        ]);
});

test('water history endpoint returns measurements', function () {
    // Create water level measurements
    for ($i = 0; $i < 5; $i++) {
        WaterLevel::create([
            'device_id' => $this->device->id,
            'measured_at' => now()->subMinutes($i),
            'level_percent' => 70 + $i,
            'liters' => 14 + ($i * 0.2),
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/water-history?device_slug=test-growdash&limit=10');

    $response->assertStatus(200);
    $history = $response->json('history');

    expect($history)->toHaveCount(5);
    expect($history[0])->toHaveKeys(['timestamp', 'level', 'liters']);
});

test('tds history endpoint returns readings', function () {
    for ($i = 0; $i < 3; $i++) {
        TdsReading::create([
            'device_id' => $this->device->id,
            'measured_at' => now()->subMinutes($i),
            'value_ppm' => 400 + ($i * 10),
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/tds-history?device_slug=test-growdash&limit=10');

    $response->assertStatus(200);
    expect($response->json('history'))->toHaveCount(3);
});

test('temperature history endpoint returns readings', function () {
    for ($i = 0; $i < 3; $i++) {
        TemperatureReading::create([
            'device_id' => $this->device->id,
            'measured_at' => now()->subMinutes($i),
            'value_c' => 20 + $i,
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/temperature-history?device_slug=test-growdash&limit=10');

    $response->assertStatus(200);
    expect($response->json('history'))->toHaveCount(3);
});

test('spray events endpoint returns events', function () {
    SprayEvent::create([
        'device_id' => $this->device->id,
        'start_time' => now()->subMinutes(10),
        'end_time' => now()->subMinutes(8),
        'duration_seconds' => 120,
        'manual' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/spray-events?device_slug=test-growdash');

    $response->assertStatus(200);
    $events = $response->json('events');

    expect($events)->toHaveCount(1);
    expect($events[0]['duration_seconds'])->toBe(120);
    expect($events[0]['manual'])->toBeTrue();
});

test('fill events endpoint returns events', function () {
    FillEvent::create([
        'device_id' => $this->device->id,
        'start_time' => now()->subMinutes(10),
        'end_time' => now()->subMinutes(5),
        'duration_seconds' => 300,
        'target_level' => 80.0,
        'target_liters' => 20.0,
        'actual_liters' => 19.5,
        'manual' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/fill-events?device_slug=test-growdash');

    $response->assertStatus(200);
    $events = $response->json('events');

    expect($events)->toHaveCount(1);
    expect($events[0]['target_level'])->toBe(80);
    expect($events[0]['actual_liters'])->toBe(19.5);
});

test('logs endpoint returns arduino logs', function () {
    ArduinoLog::create([
        'device_id' => $this->device->id,
        'logged_at' => now(),
        'level' => 'error',
        'message' => 'Sensor malfunction',
    ]);

    ArduinoLog::create([
        'device_id' => $this->device->id,
        'logged_at' => now()->subMinutes(1),
        'level' => 'info',
        'message' => 'System started',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/logs?device_slug=test-growdash');

    $response->assertStatus(200);
    expect($response->json('logs'))->toHaveCount(2);
});

test('logs endpoint filters by level', function () {
    ArduinoLog::create([
        'device_id' => $this->device->id,
        'logged_at' => now(),
        'level' => 'error',
        'message' => 'Error message',
    ]);

    ArduinoLog::create([
        'device_id' => $this->device->id,
        'logged_at' => now(),
        'level' => 'info',
        'message' => 'Info message',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/logs?device_slug=test-growdash&level=error');

    $response->assertStatus(200);
    expect($response->json('logs'))->toHaveCount(1);
    expect($response->json('logs')[0]['level'])->toBe('error');
});

test('history endpoints respect limit parameter', function () {
    for ($i = 0; $i < 20; $i++) {
        WaterLevel::create([
            'device_id' => $this->device->id,
            'measured_at' => now()->subMinutes($i),
            'level_percent' => 70,
            'liters' => 14,
        ]);
    }

    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/water-history?device_slug=test-growdash&limit=5');

    $response->assertStatus(200);
    expect($response->json('history'))->toHaveCount(5);
});

test('api endpoints return 404 for non-existent device', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/growdash/water-history?device_slug=non-existent');

    $response->assertStatus(404);
});
