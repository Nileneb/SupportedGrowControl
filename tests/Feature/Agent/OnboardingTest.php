<?php

use App\Models\Device;
use App\Models\User;

test('bootstrap creates unpaired device', function () {
    $response = $this->postJson('/api/agents/bootstrap', [
        'bootstrap_id' => 'test-device-001',
        'name' => 'Test Device',
        'board_type' => 'arduino_uno',
    ]);

    $response->assertStatus(201)
        ->assertJson(['status' => 'unpaired'])
        ->assertJsonStructure(['bootstrap_code', 'message']);

    expect(Device::where('bootstrap_id', 'test-device-001')->exists())->toBeTrue();
});

test('pairing status returns unpaired', function () {
    $device = Device::factory()->create([
        'bootstrap_id' => 'test-device-002',
        'bootstrap_code' => 'ABC123',
        'user_id' => null,
    ]);

    $response = $this->getJson('/api/agents/pairing/status?bootstrap_id=test-device-002&bootstrap_code=ABC123');

    $response->assertStatus(200)
        ->assertJson(['status' => 'unpaired']);
});

test('pairing status returns paired after user pairs', function () {
    $user = User::factory()->create();
    $device = Device::factory()->create([
        'bootstrap_id' => 'test-device-003',
        'bootstrap_code' => 'XYZ789',
        'user_id' => null,
    ]);

    $device->pairWithUser($user->id);

    $response = $this->getJson('/api/agents/pairing/status?bootstrap_id=test-device-003&bootstrap_code=XYZ789');

    $response->assertStatus(200)
        ->assertJson(['status' => 'paired'])
        ->assertJsonStructure(['public_id', 'device_token', 'device_name', 'user_email']);
});

test('device pairing by user', function () {
    $user = User::factory()->create();
    $device = Device::factory()->create([
        'bootstrap_code' => 'PAIR99',
        'user_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/devices/pair', [
            'bootstrap_code' => 'PAIR99',
        ]);

    $response->assertStatus(200)->assertJson(['success' => true]);

    $device->refresh();
    expect($device->user_id)->toBe($user->id);
    expect($device->paired_at)->not->toBeNull();
    expect($device->public_id)->not->toBeNull();
});
