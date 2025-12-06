<?php

use App\Models\ShellyDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function makeUserWithShelly(array $overrides = []): array {
    $user = User::factory()->create();
    $shelly = ShellyDevice::factory()->create(array_merge([
        'user_id' => $user->id,
        'ip_address' => '192.168.1.50',
        'model' => 'Shelly Plus 1',
    ], $overrides));

    return [$user, $shelly];
}

test('authorized user can turn on shelly gen2 device', function () {
    [$user, $shelly] = makeUserWithShelly();
    Sanctum::actingAs($user);

    Http::fake([
        '192.168.1.50/rpc/Switch.Set' => Http::response(['was_on' => false], 200),
    ]);

    $response = $this->postJson(route('api.shelly.control', ['shelly' => $shelly->id, 'action' => 'on']));

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/rpc/Switch.Set')
            && $request['on'] === true;
    });
});

test('authorized user can toggle gen2 device', function () {
    [$user, $shelly] = makeUserWithShelly();
    Sanctum::actingAs($user);

    Http::fake([
        '192.168.1.50/rpc/Switch.Set' => Http::response(['toggled' => true], 200),
    ]);

    $response = $this->postJson(route('api.shelly.control', ['shelly' => $shelly->id, 'action' => 'toggle']));

    $response->assertOk()->assertJson(['success' => true]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/rpc/Switch.Set')
            && ($request['toggle'] ?? false) === true;
    });
});

test('returns 403 when user does not own shelly device', function () {
    [$user, $shelly] = makeUserWithShelly();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($otherUser);

    $response = $this->postJson(route('api.shelly.control', ['shelly' => $shelly->id, 'action' => 'on']));

    $response->assertStatus(403);
});

test('returns 422 for invalid action', function () {
    [$user, $shelly] = makeUserWithShelly();
    Sanctum::actingAs($user);

    $response = $this->postJson(route('api.shelly.control', ['shelly' => $shelly->id, 'action' => 'blink']));

    $response->assertStatus(422);
});

test('returns error when shelly command fails', function () {
    [$user, $shelly] = makeUserWithShelly();
    Sanctum::actingAs($user);

    Http::fake([
        '192.168.1.50/rpc/Switch.Set' => Http::response(['error' => 'Device offline'], 500),
    ]);

    $response = $this->postJson(route('api.shelly.control', ['shelly' => $shelly->id, 'action' => 'on']));

    $response->assertStatus(502)
        ->assertJsonStructure(['message', 'error']);
});
