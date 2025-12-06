<?php

use App\Models\Device;
use App\Models\User;
use App\Models\WebcamFeed;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

it('registers webcams from agent', function () {
    $user = User::factory()->create();
    $plaintextToken = Str::random(64);

    $device = Device::factory()->create([
        'user_id' => $user->id,
        'paired_at' => now(),
        'agent_token' => hash('sha256', $plaintextToken),
    ]);

    $response = postJson('/api/growdash/agent/webcams', [
        'webcams' => [
            [
                'device_path' => '/dev/video0',
                'stream_endpoint' => 'http://127.0.0.1:8090/stream/webcam?device=/dev/video0',
                'name' => 'USB Camera',
            ],
        ],
    ], [
        'X-Device-ID' => $device->public_id,
        'X-Device-Token' => $plaintextToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('registered', 1);

    expect(WebcamFeed::count())->toBe(1);
    $webcam = WebcamFeed::first();
    expect($webcam->device_id)->toBe($device->id);
    expect($webcam->device_path)->toBe('/dev/video0');
    expect($webcam->is_active)->toBeTrue();
});

it('updates existing webcam when device path matches', function () {
    $user = User::factory()->create();
    $plaintextToken = Str::random(64);

    $device = Device::factory()->create([
        'user_id' => $user->id,
        'paired_at' => now(),
        'agent_token' => hash('sha256', $plaintextToken),
    ]);

    WebcamFeed::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'device_path' => '/dev/video0',
        'name' => 'Old Name',
        'stream_url' => 'http://old-url',
        'type' => 'mjpeg',
        'is_active' => true,
    ]);

    postJson('/api/growdash/agent/webcams', [
        'webcams' => [
            [
                'device_path' => '/dev/video0',
                'stream_endpoint' => 'http://new-url',
                'name' => 'New Name',
            ],
        ],
    ], [
        'X-Device-ID' => $device->public_id,
        'X-Device-Token' => $plaintextToken,
    ])
        ->assertOk()
        ->assertJsonPath('updated', 1)
        ->assertJsonPath('registered', 0);

    expect(WebcamFeed::count())->toBe(1);
    $webcam = WebcamFeed::first();
    expect($webcam->name)->toBe('New Name');
    expect($webcam->stream_url)->toBe('http://new-url');
});

it('lists webcams for device owner', function () {
    $owner = User::factory()->create();
    $device = Device::factory()->for($owner)->create();

    WebcamFeed::create([
        'user_id' => $owner->id,
        'device_id' => $device->id,
        'device_path' => '/dev/video0',
        'name' => 'Camera 1',
        'stream_url' => 'http://localhost:8090/stream',
        'type' => 'mjpeg',
        'is_active' => true,
    ]);

    actingAs($owner)
        ->getJson('/api/devices/' . $device->public_id . '/webcams')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'webcams');
});

it('forbids webcam list for non-owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $device = Device::factory()->for($owner)->create();

    WebcamFeed::create([
        'user_id' => $owner->id,
        'device_id' => $device->id,
        'device_path' => '/dev/video0',
        'name' => 'Camera',
        'stream_url' => 'http://localhost:8090/stream',
        'type' => 'mjpeg',
        'is_active' => true,
    ]);

    actingAs($stranger)
        ->getJson('/api/devices/' . $device->public_id . '/webcams')
        ->assertForbidden();
});

it('allows owner to toggle webcam active state', function () {
    $owner = User::factory()->create();
    $device = Device::factory()->for($owner)->create();

    $webcam = WebcamFeed::create([
        'user_id' => $owner->id,
        'device_id' => $device->id,
        'device_path' => '/dev/video0',
        'name' => 'Camera',
        'stream_url' => 'http://localhost:8090/stream',
        'type' => 'mjpeg',
        'is_active' => true,
    ]);

    actingAs($owner)
        ->patchJson('/api/webcams/' . $webcam->id, [
            'is_active' => false,
        ])
        ->assertOk();

    $webcam->refresh();
    expect($webcam->is_active)->toBeFalse();
});
