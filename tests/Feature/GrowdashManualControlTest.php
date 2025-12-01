<?php

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->device = Device::create([
        'name' => 'Test Growdash',
        'slug' => 'test-growdash',
        'ip_address' => '192.168.1.100',
        'serial_port' => '/dev/ttyUSB0',
    ]);

    $this->validToken = 'test-webhook-token';
    config(['services.growdash.webhook_token' => $this->validToken]);
});

test('manual spray on creates event', function () {
    $response = $this->postJson('/api/growdash/manual-spray', [
        'device_slug' => 'test-growdash',
        'action' => 'on',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'spray_active' => true,
        ]);

    $this->assertDatabaseHas('spray_events', [
        'device_id' => $this->device->id,
        'manual' => true,
    ]);

    $this->assertDatabaseHas('system_statuses', [
        'device_id' => $this->device->id,
        'spray_active' => true,
    ]);
});

test('manual spray off ends event', function () {
    // Start spray
    $this->postJson('/api/growdash/manual-spray', [
        'device_slug' => 'test-growdash',
        'action' => 'on',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    sleep(1);

    // Stop spray
    $response = $this->postJson('/api/growdash/manual-spray', [
        'device_slug' => 'test-growdash',
        'action' => 'off',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'spray_active' => false,
        ]);

    $event = $this->device->sprayEvents()->first();
    expect($event->end_time)->not->toBeNull();
    expect($event->duration_seconds)->toBeGreaterThan(0);
});

test('manual fill start creates event', function () {
    $response = $this->postJson('/api/growdash/manual-fill', [
        'device_slug' => 'test-growdash',
        'action' => 'start',
        'target_level' => 80.0,
        'target_liters' => 20.0,
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'filling_active' => true,
        ]);

    $this->assertDatabaseHas('fill_events', [
        'device_id' => $this->device->id,
        'manual' => true,
        'target_level' => 80.0,
        'target_liters' => 20.0,
    ]);
});

test('manual fill stop ends event', function () {
    // Start fill
    $this->postJson('/api/growdash/manual-fill', [
        'device_slug' => 'test-growdash',
        'action' => 'start',
        'target_level' => 80.0,
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    sleep(1);

    // Stop fill
    $response = $this->postJson('/api/growdash/manual-fill', [
        'device_slug' => 'test-growdash',
        'action' => 'stop',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'filling_active' => false,
        ]);

    $event = $this->device->fillEvents()->first();
    expect($event->end_time)->not->toBeNull();
    expect($event->duration_seconds)->toBeGreaterThan(0);
});

test('manual spray requires valid token', function () {
    $response = $this->postJson('/api/growdash/manual-spray', [
        'device_slug' => 'test-growdash',
        'action' => 'on',
    ]);

    $response->assertStatus(403);
});

test('manual fill requires valid token', function () {
    $response = $this->postJson('/api/growdash/manual-fill', [
        'device_slug' => 'test-growdash',
        'action' => 'start',
    ]);

    $response->assertStatus(403);
});

test('manual spray validates action', function () {
    $response = $this->postJson('/api/growdash/manual-spray', [
        'device_slug' => 'test-growdash',
        'action' => 'invalid',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(422);
});

test('manual fill validates action', function () {
    $response = $this->postJson('/api/growdash/manual-fill', [
        'device_slug' => 'test-growdash',
        'action' => 'invalid',
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(422);
});

test('manual fill validates target values', function () {
    $response = $this->postJson('/api/growdash/manual-fill', [
        'device_slug' => 'test-growdash',
        'action' => 'start',
        'target_level' => 150, // Invalid: > 100
    ], [
        'X-Growdash-Token' => $this->validToken,
    ]);

    $response->assertStatus(422);
});
