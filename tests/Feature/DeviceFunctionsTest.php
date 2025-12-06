<?php

use App\Models\Command;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns command history for owner respecting limit', function () {
    $user = User::factory()->create();
    $device = Device::factory()->for($user)->create(['status' => 'online']);

    Command::factory()->for($device)->create(['type' => 'serial_command', 'created_at' => now()->subMinutes(2)]);
    Command::factory()->for($device)->create(['type' => 'spray', 'created_at' => now()->subMinute()]);

    $response = actingAs($user)
        ->getJson('/api/growdash/devices/' . $device->public_id . '/commands?limit=1');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('commands.0.type', 'spray');
});

it('rejects command history access for non-owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $device = Device::factory()->for($owner)->create(['status' => 'online']);

    actingAs($stranger)
        ->getJson('/api/growdash/devices/' . $device->public_id . '/commands')
        ->assertStatus(500); // controller wraps in 500 on not found/forbidden
});

it('blocks sending command when device is offline', function () {
    $user = User::factory()->create();
    $device = Device::factory()->for($user)->create(['status' => 'offline']);

    actingAs($user)
        ->postJson('/api/growdash/devices/' . $device->public_id . '/commands', [
            'type' => 'serial_command',
            'params' => ['command' => 'Status'],
        ])
        ->assertStatus(400)
        ->assertJsonPath('device_status', 'offline');
});

it('returns device logs via api route for owner', function () {
    $user = User::factory()->create();
    $device = Device::factory()->for($user)->create();

    DeviceLog::create([
        'device_id' => $device->id,
        'level' => 'info',
        'message' => 'Pump started',
        'agent_timestamp' => now()->subMinute(),
    ]);

    $response = actingAs($user)
        ->getJson('/api/devices/' . $device->public_id . '/logs?limit=10');

    $response->assertOk()
        ->assertJsonCount(1, 'logs')
        ->assertJsonPath('logs.0.message', 'Pump started');
});

it('returns device log stats for owner and forbids stranger', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $device = Device::factory()->for($owner)->create();

    DeviceLog::create(['device_id' => $device->id, 'level' => 'info', 'message' => 'Ok']);
    DeviceLog::create(['device_id' => $device->id, 'level' => 'error', 'message' => 'Boom']);

    actingAs($owner)
        ->getJson('/api/devices/' . $device->public_id . '/logs/stats')
        ->assertOk()
        ->assertJsonPath('info', 1)
        ->assertJsonPath('error', 1);

    actingAs($stranger)
        ->getJson('/api/devices/' . $device->public_id . '/logs/stats')
        ->assertStatus(403);
});
