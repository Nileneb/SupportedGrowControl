<?php

use App\Models\ShellyDevice;
use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->device = Device::factory()->create(['user_id' => $this->user->id]);
});

// ==================== CRUD Tests ====================

test('user can create shelly device', function () {
    $shelly = ShellyDevice::create([
        'user_id' => $this->user->id,
        'device_id' => $this->device->id,
        'name' => 'Office Light',
        'shelly_device_id' => 'shellyplus1pm-abc123',
        'ip_address' => '192.168.1.100',
        'auth_token' => Str::random(32),
        'model' => 'Shelly Plus 1PM',
        'is_active' => true,
    ]);

    expect($shelly->user_id)->toBe($this->user->id);
    expect($shelly->device_id)->toBe($this->device->id);
    expect($shelly->name)->toBe('Office Light');
    expect($shelly->is_active)->toBeTrue();
});

test('shelly device belongs to user', function () {
    $shelly = ShellyDevice::factory()->create(['user_id' => $this->user->id]);

    expect($shelly->user)->toBeInstanceOf(User::class);
    expect($shelly->user->id)->toBe($this->user->id);
});

test('shelly device can be linked to grow device', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'device_id' => $this->device->id,
    ]);

    expect($shelly->device)->toBeInstanceOf(Device::class);
    expect($shelly->device->id)->toBe($this->device->id);
});

test('shelly device can be unlinked from grow device', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'device_id' => null,
    ]);

    expect($shelly->device_id)->toBeNull();
});

// ==================== Token Verification Tests ====================

test('shelly device verifies valid token', function () {
    $token = Str::random(32);
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'auth_token' => $token,
    ]);

    expect($shelly->verifyToken($token))->toBeTrue();
});

test('shelly device rejects invalid token', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'auth_token' => Str::random(32),
    ]);

    expect($shelly->verifyToken('wrong-token'))->toBeFalse();
});

// ==================== Webhook Recording Tests ====================

test('shelly device records webhook timestamp', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'last_webhook_at' => null,
        'last_seen_at' => null,
    ]);

    expect($shelly->last_webhook_at)->toBeNull();

    $shelly->recordWebhook();
    $shelly->refresh();

    expect($shelly->last_webhook_at)->not->toBeNull();
    expect($shelly->last_seen_at)->not->toBeNull();
});

// ==================== Generation Detection Tests ====================

test('detects gen2 device by plus in device id', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'shelly_device_id' => 'shellyplus1pm-abc123',
    ]);

    expect($shelly->isGen2())->toBeTrue();
});

test('detects gen2 device by pro in device id', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'shelly_device_id' => 'shellypro1pm-xyz789',
    ]);

    expect($shelly->isGen2())->toBeTrue();
});

test('detects gen2 device by model name', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'shelly_device_id' => 'shelly-abc',
        'model' => 'Shelly Gen2 1PM',
    ]);

    expect($shelly->isGen2())->toBeTrue();
});

test('detects gen1 device', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'shelly_device_id' => 'shelly1pm-abc123',
        'model' => 'Shelly 1PM',
    ]);

    expect($shelly->isGen2())->toBeFalse();
});

// ==================== Webhook URL Tests ====================

test('shelly device generates webhook url', function () {
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'auth_token' => 'test-token-123',
    ]);

    $url = $shelly->getWebhookUrl();

    expect($url)->toContain('/api/shelly/webhook/' . $shelly->id);
    expect($url)->toContain('token=test-token-123');
});

// ==================== Config Storage Tests ====================

test('shelly device stores config as json', function () {
    $config = [
        'switch:0' => ['name' => 'Main Switch'],
        'wifi' => ['ssid' => 'TestNetwork'],
    ];

    $shelly = ShellyDevice::factory()->create([
        'user_id' => $this->user->id,
        'config' => $config,
    ]);

    expect($shelly->config)->toBe($config);
    expect($shelly->config['switch:0']['name'])->toBe('Main Switch');
});
