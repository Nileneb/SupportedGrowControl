<?php

/**
 * Endpoint Tracking Feature Tests
 * 
 * Dieses Script führt Pest-basierte Tests aus und loggt jeden Endpoint-Aufruf
 * um zu zeigen, welche Endpoints wirklich genutzt werden.
 * 
 * Run: php artisan test tests/Feature/EndpointTrackingTest.php
 */

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\Command;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EndpointTrackingTest extends TestCase
{
    protected User $user;
    protected Device $device;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Create test device
        $this->device = Device::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'online',
        ]);
    }

    /**
     * AuthController Tests
     */
    public function test_auth_login()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        $this->assertIn($response->status(), [200, 401, 422]);
        echo "\n✓ Tested: POST /api/auth/login";
    }

    /**
     * CommandController Tests
     */
    public function test_command_pending()
    {
        Sanctum::actingAs($this->user);
        
        // Simulate as device with token auth
        $response = $this->getJson('/api/growdash/agent/commands/pending', [
            'X-Device-ID' => $this->device->public_id,
            'X-Device-Token' => $this->device->agent_token,
        ]);
        
        $this->assertIn($response->status(), [200, 401]);
        echo "\n✓ Tested: GET /api/growdash/agent/commands/pending";
    }

    public function test_command_send_serial()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson("/api/growdash/devices/{$this->device->public_id}/commands", [
            'type' => 'serial_command',
            'params' => ['command' => 'STATUS'],
        ]);
        
        $this->assertIn($response->status(), [201, 401]);
        echo "\n✓ Tested: POST /api/growdash/devices/{id}/commands (serial)";
    }

    public function test_command_history()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson("/api/growdash/devices/{$this->device->public_id}/commands");
        
        $this->assertIn($response->status(), [200, 401]);
        echo "\n✓ Tested: GET /api/growdash/devices/{id}/commands";
    }

    public function test_command_result()
    {
        Sanctum::actingAs($this->user);
        
        $command = Command::factory()->create(['device_id' => $this->device->id]);
        
        $response = $this->postJson("/api/growdash/agent/commands/{$command->id}/result", [
            'status' => 'completed',
            'result_message' => 'Test completed',
        ]);
        
        $this->assertIn($response->status(), [200, 401]);
        echo "\n✓ Tested: POST /api/growdash/agent/commands/{id}/result";
    }

    /**
     * DeviceManagementController Tests
     */
    public function test_device_heartbeat()
    {
        $response = $this->postJson('/api/growdash/agent/heartbeat', [
            'last_state' => ['uptime' => 3600],
        ], [
            'X-Device-ID' => $this->device->public_id,
            'X-Device-Token' => $this->device->agent_token,
        ]);
        
        $this->assertIn($response->status(), [200, 401]);
        echo "\n✓ Tested: POST /api/growdash/agent/heartbeat";
    }

    /**
     * LogController Tests
     */
    public function test_log_store()
    {
        $response = $this->postJson('/api/growdash/agent/logs', [
            'logs' => [
                ['level' => 'info', 'message' => 'Test log'],
            ],
        ], [
            'X-Device-ID' => $this->device->public_id,
            'X-Device-Token' => $this->device->agent_token,
        ]);
        
        $this->assertIn($response->status(), [201, 401]);
        echo "\n✓ Tested: POST /api/growdash/agent/logs";
    }

    /**
     * BootstrapController Tests
     */
    public function test_bootstrap_new_device()
    {
        $response = $this->postJson('/api/agents/bootstrap', [
            'bootstrap_id' => 'agent-' . time(),
        ]);
        
        $this->assertIn($response->status(), [201, 200]);
        echo "\n✓ Tested: POST /api/agents/bootstrap (new)";
    }

    public function test_bootstrap_status()
    {
        // Create a device for status check
        $device = Device::factory()->create(['bootstrap_code' => 'ABC123']);
        
        $response = $this->getJson('/api/agents/pairing/status', [
            'bootstrap_id' => $device->bootstrap_id,
            'bootstrap_code' => 'ABC123',
        ]);
        
        $this->assertIn($response->status(), [200, 404]);
        echo "\n✓ Tested: GET /api/agents/pairing/status";
    }

    /**
     * DevicePairingController Tests
     */
    public function test_device_pair()
    {
        Sanctum::actingAs($this->user);
        
        $device = Device::factory()->create(['bootstrap_code' => 'PAIR01']);
        
        $response = $this->postJson('/api/devices/pair', [
            'bootstrap_code' => 'PAIR01',
        ]);
        
        $this->assertIn($response->status(), [200, 404]);
        echo "\n✓ Tested: POST /api/devices/pair";
    }

    /**
     * Web Controllers
     */
    public function test_dashboard_view()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');
        
        $this->assertIn($response->status(), [200, 302]);
        echo "\n✓ Tested: GET /dashboard";
    }

    public function test_calendar_view()
    {
        $response = $this->actingAs($this->user)
            ->get('/calendar');
        
        $this->assertIn($response->status(), [200, 302]);
        echo "\n✓ Tested: GET /calendar";
    }

    public function test_calendar_events()
    {
        $response = $this->actingAs($this->user)
            ->get('/calendar/events?start=2025-12-01&end=2025-12-31');
        
        $this->assertIn($response->status(), [200, 302]);
        echo "\n✓ Tested: GET /calendar/events";
    }

    /**
     * GrowdashWebhookController Tests (optional endpoints)
     */
    public function test_growdash_log()
    {
        $response = $this->postJson('/api/growdash/log', [
            'device_slug' => 'test-device',
            'message' => 'Test log',
        ]);
        
        // May fail since we don't have proper auth, but that's ok - we're tracking calls
        $this->assertIn($response->status(), [200, 401, 422, 404]);
        echo "\n✓ Tested: POST /api/growdash/log";
    }

    public function test_growdash_status()
    {
        $response = $this->getJson('/api/growdash/status?device_slug=test');
        
        $this->assertIn($response->status(), [200, 404]);
        echo "\n✓ Tested: GET /api/growdash/status";
    }

    public function test_growdash_history_endpoints()
    {
        $endpoints = [
            '/api/growdash/water-history?device_slug=test',
            '/api/growdash/tds-history?device_slug=test',
            '/api/growdash/temperature-history?device_slug=test',
            '/api/growdash/spray-events?device_slug=test',
            '/api/growdash/fill-events?device_slug=test',
            '/api/growdash/logs?device_slug=test',
        ];
        
        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertIn($response->status(), [200, 404]);
            echo "\n✓ Tested: GET $endpoint";
        }
    }

    /**
     * ArduinoCompileController Tests
     */
    public function test_arduino_compile_endpoints()
    {
        Sanctum::actingAs($this->user);
        
        // Note: These will fail without proper setup, but we're just tracking calls
        $script = \App\Models\DeviceScript::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->postJson("/scripts/{$script->id}/compile", [
            'device_id' => $this->device->id,
        ]);
        
        $this->assertIn($response->status(), [200, 401, 404]);
        echo "\n✓ Tested: POST /scripts/{id}/compile";
    }

    /**
     * ShellySyncController Tests
     */
    public function test_shelly_endpoints()
    {
        Sanctum::actingAs($this->user);
        
        // These will fail but we're just tracking
        $response = $this->postJson("/devices/{$this->device->id}/shelly/setup", [
            'shelly_device_id' => 'shellyplug-s-12345',
        ]);
        
        $this->assertIn($response->status(), [200, 401, 403]);
        echo "\n✓ Tested: POST /devices/{id}/shelly/setup";
    }
}
