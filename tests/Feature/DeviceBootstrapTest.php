<?php

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ==================== Bootstrap Flow Tests ====================

test('agent bootstrap creates new unclaimed device', function () {
    $response = $this->postJson('/api/agents/bootstrap', [
        'bootstrap_id' => 'agent-12345',
        'name' => 'Test Device',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'unpaired',
        ])
        ->assertJsonStructure(['bootstrap_code', 'message']);

    $this->assertDatabaseHas('devices', [
        'bootstrap_id' => 'agent-12345',
        'name' => 'Test Device',
        'user_id' => null,
    ]);

    $device = Device::where('bootstrap_id', 'agent-12345')->first();
    expect($device->bootstrap_code)->toHaveLength(6);
    expect($device->public_id)->toBeNull();
    expect($device->agent_token)->toBeNull();
});

test('agent bootstrap returns unpaired status for existing unclaimed device', function () {
    $device = Device::create([
        'bootstrap_id' => 'agent-existing',
        'name' => 'Existing Device',
        'slug' => 'device-existing',
    ]);

    $response = $this->postJson('/api/agents/bootstrap', [
        'bootstrap_id' => 'agent-existing',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'unpaired',
            'bootstrap_code' => $device->bootstrap_code,
        ]);
});

test('agent bootstrap returns paired credentials for paired device', function () {
    $user = User::factory()->create();
    $device = Device::create([
        'bootstrap_id' => 'agent-paired',
        'name' => 'Paired Device',
        'slug' => 'device-paired',
    ]);
    $device->pairWithUser($user->id);

    $response = $this->postJson('/api/agents/bootstrap', [
        'bootstrap_id' => 'agent-paired',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'paired',
            'device_name' => 'Paired Device',
        ])
        ->assertJsonStructure(['public_id', 'device_token']);

    expect($response->json('public_id'))->toBe($device->public_id);
});

// ==================== Pairing Flow Tests ====================

test('user can pair unclaimed device with bootstrap code', function () {
    $user = User::factory()->create();
    $device = Device::create([
        'bootstrap_id' => 'agent-to-pair',
        'name' => 'Device To Pair',
        'slug' => 'device-to-pair',
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/devices/pair', [
            'bootstrap_code' => $device->bootstrap_code,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure(['device' => ['id', 'name', 'public_id', 'paired_at']]);

    $device->refresh();
    expect($device->user_id)->toBe($user->id);
    expect($device->public_id)->not->toBeNull();
    expect($device->agent_token)->not->toBeNull();
    expect($device->paired_at)->not->toBeNull();
});

test('pairing requires authentication', function () {
    $device = Device::create([
        'bootstrap_id' => 'agent-auth-test',
        'name' => 'Auth Test Device',
        'slug' => 'device-auth-test',
    ]);

    $response = $this->postJson('/api/devices/pair', [
        'bootstrap_code' => $device->bootstrap_code,
    ]);

    $response->assertStatus(401);
});

test('pairing with invalid code returns 404', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/devices/pair', [
            'bootstrap_code' => 'ABCDEF', // Valid format but doesn't exist
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
        ]);
});

test('pairing already paired device returns 404', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $device = Device::create([
        'bootstrap_id' => 'agent-already-paired',
        'name' => 'Already Paired',
        'slug' => 'device-already-paired',
    ]);
    $code = $device->bootstrap_code;
    $device->pairWithUser($user1->id);

    $response = $this->actingAs($user2)
        ->postJson('/api/devices/pair', [
            'bootstrap_code' => $code,
        ]);

    $response->assertStatus(404);
});

// ==================== Device Auth Middleware Tests ====================

test('device auth middleware accepts valid credentials', function () {
    $user = User::factory()->create();
    $device = Device::create([
        'bootstrap_id' => 'agent-middleware-test',
        'name' => 'Middleware Test',
        'slug' => 'device-middleware-test',
    ]);
    $device->pairWithUser($user->id);

    // Mock endpoint that uses device.auth middleware
    Route::get('/api/test-device-auth', function (Illuminate\Http\Request $request) {
        return response()->json([
            'device_id' => $request->attributes->get('device')->id,
        ]);
    })->middleware('device.auth');

    $response = $this->getJson('/api/test-device-auth', [
        'X-Device-ID' => $device->public_id,
        'X-Device-Token' => $device->agent_token,
    ]);

    $response->assertStatus(200)
        ->assertJson(['device_id' => $device->id]);
});

test('device auth middleware rejects missing headers', function () {
    Route::get('/api/test-device-auth', function () {
        return response()->json(['success' => true]);
    })->middleware('device.auth');

    $response = $this->getJson('/api/test-device-auth');

    $response->assertStatus(401)
        ->assertJson(['error' => 'Missing device credentials']);
});

test('device auth middleware rejects invalid token', function () {
    $user = User::factory()->create();
    $device = Device::create([
        'bootstrap_id' => 'agent-invalid-token',
        'name' => 'Invalid Token Test',
        'slug' => 'device-invalid-token',
    ]);
    $device->pairWithUser($user->id);

    Route::get('/api/test-device-auth', function () {
        return response()->json(['success' => true]);
    })->middleware('device.auth');

    $response = $this->getJson('/api/test-device-auth', [
        'X-Device-ID' => $device->public_id,
        'X-Device-Token' => 'wrong-token',
    ]);

    $response->assertStatus(403)
        ->assertJson(['error' => 'Invalid credentials']);
});

test('device auth middleware rejects unpaired device', function () {
    $device = Device::create([
        'bootstrap_id' => 'agent-unpaired',
        'name' => 'Unpaired Device',
        'slug' => 'device-unpaired',
    ]);

    Route::get('/api/test-device-auth', function () {
        return response()->json(['success' => true]);
    })->middleware('device.auth');

    $response = $this->getJson('/api/test-device-auth', [
        'X-Device-ID' => 'some-id',
        'X-Device-Token' => 'some-token',
    ]);

    $response->assertStatus(404)
        ->assertJson(['error' => 'Device not found']);
});
