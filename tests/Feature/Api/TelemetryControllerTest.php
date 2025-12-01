<?php

use App\Models\Device;
use App\Models\TelemetryReading;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\postJson;

describe('TelemetryController', function () {
    test('stores telemetry data successfully', function () {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $plaintextToken = Str::random(64);
        $device->update(['agent_token' => hash('sha256', $plaintextToken)]);

        $payload = [
            'readings' => [
                [
                    'sensor_key' => 'water_level',
                    'value' => 75.5,
                    'unit' => '%',
                    'measured_at' => now()->toISOString(),
                ],
                [
                    'sensor_key' => 'tds',
                    'value' => 850,
                    'unit' => 'ppm',
                    'measured_at' => now()->toISOString(),
                ],
            ],
        ];

        $response = postJson('/api/growdash/agent/telemetry', $payload, [
            'X-Device-ID' => $device->public_id,
            'X-Device-Token' => $plaintextToken,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'inserted_count' => 2,
            ]);

        expect(TelemetryReading::count())->toBe(2);
        expect(TelemetryReading::first()->sensor_key)->toBe('water_level');
    });

    test('rejects invalid telemetry data', function () {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $plaintextToken = Str::random(64);
        $device->update(['agent_token' => hash('sha256', $plaintextToken)]);

        $payload = [
            'readings' => [
                [
                    'sensor_key' => 'water_level',
                    // Missing 'value' and 'measured_at'
                ],
            ],
        ];

        $response = postJson('/api/growdash/agent/telemetry', $payload, [
            'X-Device-ID' => $device->public_id,
            'X-Device-Token' => $plaintextToken,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['success', 'errors']);
    });

    test('rejects request without device authentication', function () {
        $payload = [
            'readings' => [
                [
                    'sensor_key' => 'water_level',
                    'value' => 75.5,
                    'unit' => '%',
                    'measured_at' => now()->toISOString(),
                ],
            ],
        ];

        $response = postJson('/api/growdash/agent/telemetry', $payload);

        $response->assertStatus(401);
    });
});
