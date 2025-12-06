<?php

use App\Models\ShellyDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeShellyWithToken(): array {
    $user = User::factory()->create();
    $token = Str::random(32);
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $user->id,
        'auth_token' => $token,
        'config' => ['existing' => 'keep'],
        'last_webhook_at' => null,
        'last_seen_at' => null,
    ]);

    return [$shelly, $token];
}

test('accepts webhook with valid token and stores payload', function () {
    [$shelly, $token] = makeShellyWithToken();

    $payload = [
        'event' => 'status',
        'data' => ['switch:0' => ['output' => true]],
    ];

    $response = $this->postJson(route('api.shelly.webhook', ['shelly' => $shelly->id, 'token' => $token]), $payload);

    $response->assertOk()->assertJson(['success' => true]);

    $shelly->refresh();
    expect($shelly->last_webhook_at)->not->toBeNull();
    expect($shelly->last_seen_at)->not->toBeNull();
    expect($shelly->config['existing'])->toBe('keep');
    expect($shelly->config['last_webhook_payload'])->toMatchArray($payload);
    expect($shelly->config['last_webhook_timestamp'])->not->toBeNull();
});

test('rejects webhook with missing token', function () {
    [$shelly] = makeShellyWithToken();

    $response = $this->postJson(route('api.shelly.webhook', ['shelly' => $shelly->id]));

    $response->assertStatus(403)->assertJson(['error' => 'Invalid or missing token']);

    $shelly->refresh();
    expect($shelly->last_webhook_at)->toBeNull();
});

test('rejects webhook with invalid token', function () {
    [$shelly] = makeShellyWithToken();

    $response = $this->postJson(route('api.shelly.webhook', ['shelly' => $shelly->id, 'token' => 'wrong']));

    $response->assertStatus(403)->assertJson(['error' => 'Invalid or missing token']);

    $shelly->refresh();
    expect($shelly->last_webhook_at)->toBeNull();
});
