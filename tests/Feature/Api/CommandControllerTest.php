<?php

use App\Models\Command;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

describe('CommandController', function () {
    test('retrieves pending commands for device', function () {
        $user = User::factory()->create();
        $plaintextToken = Str::random(64);

        $device = Device::factory()->create([
            'user_id' => $user->id,
            'paired_at' => now(),
            'agent_token' => hash('sha256', $plaintextToken),
        ]);

        // Create pending and completed commands
        Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => $user->id,
            'type' => 'spray',
            'params' => ['seconds' => 10],
            'status' => 'pending',
        ]);

        Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => $user->id,
            'type' => 'fill',
            'params' => ['level' => 80],
            'status' => 'completed',
        ]);

        $response = getJson('/api/growdash/agent/commands/pending', [
            'X-Device-ID' => $device->public_id,
            'X-Device-Token' => $plaintextToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        expect($response->json('commands'))->toHaveCount(1);
        expect($response->json('commands.0.type'))->toBe('spray');
    });

    test('updates command status successfully', function () {
        $user = User::factory()->create();
        $plaintextToken = Str::random(64);

        $device = Device::factory()->create([
            'user_id' => $user->id,
            'paired_at' => now(),
            'agent_token' => hash('sha256', $plaintextToken),
        ]);

        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => $user->id,
            'type' => 'spray',
            'params' => ['seconds' => 10],
            'status' => 'pending',
        ]);

        $payload = [
            'status' => 'completed',
            'result_message' => 'Spray completed successfully',
        ];

        $response = postJson("/api/growdash/agent/commands/{$command->id}/result", $payload, [
            'X-Device-ID' => $device->public_id,
            'X-Device-Token' => $plaintextToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $command->refresh();
        expect($command->status)->toBe('completed');
        expect($command->result_message)->toBe('Spray completed successfully');
        expect($command->completed_at)->not->toBeNull();
    });

    test('rejects invalid command status', function () {
        $user = User::factory()->create();
        $plaintextToken = Str::random(64);

        $device = Device::factory()->create([
            'user_id' => $user->id,
            'paired_at' => now(),
            'agent_token' => hash('sha256', $plaintextToken),
        ]);

        $command = Command::create([
            'device_id' => $device->id,
            'created_by_user_id' => $user->id,
            'type' => 'spray',
            'params' => ['seconds' => 10],
            'status' => 'pending',
        ]);

        $payload = [
            'status' => 'invalid_status',
            'result_message' => 'Test',
        ];

        $response = postJson("/api/growdash/agent/commands/{$command->id}/result", $payload, [
            'X-Device-ID' => $device->public_id,
            'X-Device-Token' => $plaintextToken,
        ]);

        $response->assertStatus(422);
    });
});
