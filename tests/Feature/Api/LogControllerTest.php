<?php

use App\Models\Device;
use App\Models\DeviceLog;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\postJson;

describe('LogController', function () {
    test('stores device logs successfully', function () {
        $user = User::factory()->create();
        $plaintextToken = Str::random(64);

        $device = Device::factory()->create([
            'user_id' => $user->id,
            'paired_at' => now(),
            'agent_token' => hash('sha256', $plaintextToken),
        ]);

        $payload = [
            'logs' => [
                [
                    'level' => 'info',
                    'message' => 'Device booted successfully',
                    'context' => ['uptime' => 120, 'memory' => 45000],
                ],
                [
                    'level' => 'error',
                    'message' => 'Failed to read TDS sensor',
                    'context' => ['sensor' => 'tds', 'error_code' => 'TIMEOUT'],
                ],
            ],
        ];

        $response = postJson('/api/growdash/agent/logs', $payload, [
            'X-Device-ID' => $device->public_id,
            'X-Device-Token' => $plaintextToken,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'inserted_count' => 2,
            ]);

        expect(DeviceLog::count())->toBe(2);
        expect(DeviceLog::first()->level)->toBe('info');
        expect(DeviceLog::orderBy('id', 'desc')->first()->level)->toBe('error');
    });

    test('rejects invalid log level', function () {
        $user = User::factory()->create();
        $plaintextToken = Str::random(64);

        $device = Device::factory()->create([
            'user_id' => $user->id,
            'paired_at' => now(),
            'agent_token' => hash('sha256', $plaintextToken),
        ]);

        $payload = [
            'logs' => [
                [
                    'level' => 'invalid_level',
                    'message' => 'Test message',
                ],
            ],
        ];

        $response = postJson('/api/growdash/agent/logs', $payload, [
            'X-Device-ID' => $device->public_id,
            'X-Device-Token' => $plaintextToken,
        ]);

        $response->assertStatus(422);
    });
});
