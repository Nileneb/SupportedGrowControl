<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('preserves session when navigating between authenticated pages', function () {
    $user = User::factory()->create();

    // Login
    $loginResponse = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $loginResponse->assertRedirect(route('dashboard'));

    // Get dashboard
    $dashboardResponse = $this->get(route('dashboard'));
    $dashboardResponse->assertOk();

    // Navigate to devices
    $devicesResponse = $this->get(route('devices.index'));
    $devicesResponse->assertOk();

    // Navigate back to dashboard - should still be authenticated
    $backToDashboard = $this->get(route('dashboard'));
    $backToDashboard->assertOk();
    
    // Ensure still authenticated
    $this->assertAuthenticated();
});

it('does not logout when clicking sidebar links', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // Simulate multiple navigation clicks
    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticated();

    $this->get(route('devices.index'))->assertOk();
    $this->assertAuthenticated();

    $this->get(route('calendar.index'))->assertOk();
    $this->assertAuthenticated();

    $this->get(route('logs.index'))->assertOk();
    $this->assertAuthenticated();

    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticated();
});

it('maintains session across POST requests', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // Simulate form submission
    $response = $this->post(route('feedback.store'), [
        'message' => 'Test feedback',
    ]);

    $response->assertRedirect();
    
    // Should still be authenticated after POST
    $this->assertAuthenticated();
    
    // Should be able to navigate
    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticated();
});
