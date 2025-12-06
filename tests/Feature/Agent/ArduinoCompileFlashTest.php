<?php

use App\Models\Device;
use App\Models\User;
use App\Models\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->device = Device::factory()->create(['user_id' => $this->user->id]);

    // Pair device to get auth token
    $this->plaintextToken = $this->device->pairWithUser($this->user->id);
});

// ==================== Arduino Compile Tests ====================

test('agent can request arduino compile', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/compile', [
        'code' => 'void setup() {} void loop() {}',
        'board' => 'arduino:avr:uno',
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Compile command queued',
        ])
        ->assertJsonStructure(['command_id']);

    $this->assertDatabaseHas('commands', [
        'device_id' => $this->device->id,
        'type' => 'arduino_compile',
        'status' => 'pending',
    ]);
});

test('arduino compile requires code parameter', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/compile', [
        'board' => 'arduino:avr:uno',
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['code']);
});

test('arduino compile requires board parameter', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/compile', [
        'code' => 'void setup() {} void loop() {}',
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['board']);
});

test('arduino compile stores code and board in params', function () {
    $code = 'void setup() { pinMode(13, OUTPUT); } void loop() { digitalWrite(13, HIGH); }';
    $board = 'arduino:avr:mega';

    $response = $this->postJson('/api/growdash/agent/arduino/compile', [
        'code' => $code,
        'board' => $board,
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);

    $command = Command::where('type', 'arduino_compile')->first();

    expect($command->params['code'])->toBe($code);
    expect($command->params['board'])->toBe($board);
});

test('arduino compile requires device authentication', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/compile', [
        'code' => 'void setup() {} void loop() {}',
        'board' => 'arduino:avr:uno',
    ]);

    $response->assertStatus(401);
});

// ==================== Arduino Upload Tests ====================

test('agent can request arduino upload', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/upload', [
        'code' => 'void setup() {} void loop() {}',
        'board' => 'arduino:avr:uno',
        'port' => '/dev/ttyUSB0',
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Upload command queued',
        ])
        ->assertJsonStructure(['command_id']);

    $this->assertDatabaseHas('commands', [
        'device_id' => $this->device->id,
        'type' => 'arduino_upload',
        'status' => 'pending',
    ]);
});

test('arduino upload requires code parameter', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/upload', [
        'board' => 'arduino:avr:uno',
        'port' => '/dev/ttyUSB0',
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('arduino upload requires board parameter', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/upload', [
        'code' => 'void setup() {} void loop() {}',
        'port' => '/dev/ttyUSB0',
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['board']);
});

test('arduino upload requires port parameter', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/upload', [
        'code' => 'void setup() {} void loop() {}',
        'board' => 'arduino:avr:uno',
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['port']);
});

test('arduino upload stores all params correctly', function () {
    $code = 'void setup() {} void loop() { delay(1000); }';
    $board = 'esp32:esp32:esp32';
    $port = 'COM3';

    $response = $this->postJson('/api/growdash/agent/arduino/upload', [
        'code' => $code,
        'board' => $board,
        'port' => $port,
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);

    $command = Command::where('type', 'arduino_upload')->first();

    expect($command->params['code'])->toBe($code);
    expect($command->params['board'])->toBe($board);
    expect($command->params['port'])->toBe($port);
});

test('arduino upload requires device authentication', function () {
    $response = $this->postJson('/api/growdash/agent/arduino/upload', [
        'code' => 'void setup() {} void loop() {}',
        'board' => 'arduino:avr:uno',
        'port' => '/dev/ttyUSB0',
    ]);

    $response->assertStatus(401);
});

// ==================== Port Scan Tests ====================

test('agent can request port scan', function () {
    $response = $this->getJson('/api/growdash/agent/ports/scan', [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Port scan command queued',
        ])
        ->assertJsonStructure(['command_id']);

    $this->assertDatabaseHas('commands', [
        'device_id' => $this->device->id,
        'type' => 'scan_ports',
        'status' => 'pending',
    ]);
});

test('port scan requires device authentication', function () {
    $response = $this->getJson('/api/growdash/agent/ports/scan');

    $response->assertStatus(401);
});

test('port scan command has empty params', function () {
    $response = $this->getJson('/api/growdash/agent/ports/scan', [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);

    $command = Command::where('type', 'scan_ports')->first();

    expect($command->params)->toBe([]);
});

// ==================== Command Result Reporting Tests ====================

test('agent can report compile success', function () {
    $command = Command::create([
        'device_id' => $this->device->id,
        'type' => 'arduino_compile',
        'params' => ['code' => 'test', 'board' => 'uno'],
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/growdash/agent/commands/{$command->id}/result", [
        'status' => 'completed',
        'result_message' => 'Compilation successful',
        'result_data' => [
            'output' => 'Sketch uses 1234 bytes',
            'hex_size' => 1234,
        ],
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $command->refresh();

    expect($command->status)->toBe('completed');
    expect($command->result_message)->toBe('Compilation successful');
    expect($command->result_data['output'])->toBe('Sketch uses 1234 bytes');
    expect($command->result_data['hex_size'])->toBe(1234);
});

test('agent can report compile failure', function () {
    $command = Command::create([
        'device_id' => $this->device->id,
        'type' => 'arduino_compile',
        'params' => ['code' => 'invalid code', 'board' => 'uno'],
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/growdash/agent/commands/{$command->id}/result", [
        'status' => 'failed',
        'result_message' => 'expected `;` before `}`',
        'result_data' => [
            'error' => 'Compilation error',
            'exit_code' => 1,
        ],
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);

    $command->refresh();

    expect($command->status)->toBe('failed');
    expect($command->result_message)->toContain('expected `;`');
});

test('agent can report upload success', function () {
    $command = Command::create([
        'device_id' => $this->device->id,
        'type' => 'arduino_upload',
        'params' => ['code' => 'test', 'board' => 'uno', 'port' => '/dev/ttyUSB0'],
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/growdash/agent/commands/{$command->id}/result", [
        'status' => 'completed',
        'result_message' => 'Upload successful',
        'result_data' => [
            'bytes_uploaded' => 1234,
            'duration' => 5.2,
        ],
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);

    $command->refresh();

    expect($command->status)->toBe('completed');
    expect($command->result_data['bytes_uploaded'])->toBe(1234);
});

test('agent can report port scan results', function () {
    $command = Command::create([
        'device_id' => $this->device->id,
        'type' => 'scan_ports',
        'params' => [],
        'status' => 'pending',
    ]);

    $ports = [
        ['port' => '/dev/ttyUSB0', 'description' => 'USB Serial', 'hwid' => 'USB VID:PID=2341:0043'],
        ['port' => '/dev/ttyUSB1', 'description' => 'USB Serial', 'hwid' => 'USB VID:PID=1A86:7523'],
    ];

    $response = $this->postJson("/api/growdash/agent/commands/{$command->id}/result", [
        'status' => 'completed',
        'result_message' => 'Found 2 ports',
        'result_data' => [
            'ports' => $ports,
        ],
    ], [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);

    $command->refresh();

    expect($command->status)->toBe('completed');
    expect($command->result_data['ports'])->toHaveCount(2);
    expect($command->result_data['ports'][0]['port'])->toBe('/dev/ttyUSB0');
});

// ==================== Command Polling Tests ====================

test('agent receives pending compile command', function () {
    Command::create([
        'device_id' => $this->device->id,
        'type' => 'arduino_compile',
        'params' => ['code' => 'void setup() {}', 'board' => 'uno'],
        'status' => 'pending',
    ]);

    $response = $this->getJson('/api/growdash/agent/commands/pending', [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'commands' => [
                '*' => ['id', 'type', 'params', 'status'],
            ],
        ]);

    $commands = $response->json('commands');
    expect($commands)->toHaveCount(1);
    expect($commands[0]['type'])->toBe('arduino_compile');
});

test('agent receives pending upload command', function () {
    Command::create([
        'device_id' => $this->device->id,
        'type' => 'arduino_upload',
        'params' => ['code' => 'test', 'board' => 'uno', 'port' => '/dev/ttyUSB0'],
        'status' => 'pending',
    ]);

    $response = $this->getJson('/api/growdash/agent/commands/pending', [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);

    $commands = $response->json('commands');
    expect($commands)->toHaveCount(1);
    expect($commands[0]['type'])->toBe('arduino_upload');
    expect($commands[0]['params']['port'])->toBe('/dev/ttyUSB0');
});

test('agent does not receive commands for other devices', function () {
    $otherDevice = Device::factory()->create(['user_id' => $this->user->id]);
    $otherToken = $otherDevice->pairWithUser($this->user->id);

    // Create command for other device
    Command::create([
        'device_id' => $otherDevice->id,
        'type' => 'arduino_compile',
        'params' => ['code' => 'test', 'board' => 'uno'],
        'status' => 'pending',
    ]);

    // Current device should not see it
    $response = $this->getJson('/api/growdash/agent/commands/pending', [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);
    $commands = $response->json('commands');
    expect($commands)->toHaveCount(0);
});

test('agent does not receive completed commands', function () {
    Command::create([
        'device_id' => $this->device->id,
        'type' => 'arduino_compile',
        'params' => ['code' => 'test', 'board' => 'uno'],
        'status' => 'completed',
    ]);

    $response = $this->getJson('/api/growdash/agent/commands/pending', [
        'X-Device-ID' => $this->device->public_id,
        'X-Device-Token' => $this->plaintextToken,
    ]);

    $response->assertStatus(200);
    $commands = $response->json('commands');
    expect($commands)->toHaveCount(0);
});
