<?php

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('redirects guests from device logs data', function () {
    $device = Device::factory()->create();

    get(route('devices.logs.data', $device))
        ->assertRedirect(route('login'));
});

it('returns device logs for owning user with session auth', function () {
    $user = User::factory()->create();
    $device = Device::factory()->for($user)->create();

    DeviceLog::create([
        'device_id' => $device->id,
        'level' => 'info',
        'message' => 'Pump started',
    ]);

    DeviceLog::create([
        'device_id' => $device->id,
        'level' => 'warning',
        'message' => 'Low water level',
    ]);

    actingAs($user)
        ->getJson(route('devices.logs.data', $device) . '?limit=5')
        ->assertOk()
        ->assertJsonCount(2, 'logs')
        ->assertJsonPath('device.name', $device->name);
});

it('blocks access to logs for other users', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $device = Device::factory()->for($owner)->create();

    actingAs($stranger)
        ->getJson(route('devices.logs.data', $device))
        ->assertForbidden();
});

it('authenticates broadcasting with session cookies', function () {
    $user = User::factory()->create();
    $device = Device::factory()->for($user)->create();

    actingAs($user)
        ->postJson('/broadcasting/auth', [
            'channel_name' => 'private-device.' . $device->id,
            'socket_id' => '123.456',
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

it('rejects broadcasting auth for guests', function () {
    postJson('/broadcasting/auth', [
        'channel_name' => 'private-device.' . Str::uuid(),
        'socket_id' => '123.456',
    ])->assertStatus(403);
});

it('sends serial command via session auth', function () {
    $user = User::factory()->create();
    $device = Device::factory()->for($user)->create(['status' => 'online']);

    actingAs($user)
        ->postJson('/api/growdash/devices/' . $device->public_id . '/commands', [
            'type' => 'serial_command',
            'params' => ['command' => 'Status'],
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['command_id']);
});

it('rejects serial command for guests', function () {
    $device = Device::factory()->create(['status' => 'online']);

    postJson('/api/growdash/devices/' . $device->public_id . '/commands', [
        'type' => 'serial_command',
        'params' => ['command' => 'Status'],
    ])->assertStatus(302); // redirected to login
});
