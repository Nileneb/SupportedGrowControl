<?php

use App\Models\Event;
use App\Models\Calendar;
use App\Models\ShellyDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('scheduler processes scheduled event with shelly action', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $user->id,
        'ip_address' => '192.168.1.100',
        'model' => 'Shelly Plus 1',
    ]);

    Http::fake([
        '192.168.1.100/rpc/Switch.Set' => Http::response(['was_on' => false], 200),
    ]);

    $event = Event::create([
        'user_id' => $user->id,
        'calendar_id' => $calendar->id,
        'title' => 'Turn on lights',
        'start_at' => now()->subMinute(),
        'end_at' => now()->addHour(),
        'status' => 'scheduled',
        'meta' => [
            'shelly_device_id' => $shelly->id,
            'action' => 'on',
        ],
    ]);

    Artisan::call('events:process');

    $event->refresh();
    expect($event->last_executed_at)->not->toBeNull();
    expect($event->status)->toBe('completed');
});

test('scheduler does not process future events', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);
    $shelly = ShellyDevice::factory()->create(['user_id' => $user->id]);

    $event = Event::create([
        'user_id' => $user->id,
        'calendar_id' => $calendar->id,
        'title' => 'Future event',
        'start_at' => now()->addHour(),
        'end_at' => now()->addHours(2),
        'status' => 'scheduled',
        'meta' => ['shelly_device_id' => $shelly->id, 'action' => 'on'],
    ]);

    Artisan::call('events:process');

    $event->refresh();
    expect($event->last_executed_at)->toBeNull();
    expect($event->status)->toBe('scheduled');
});

test('scheduler does not process completed events', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);
    $shelly = ShellyDevice::factory()->create(['user_id' => $user->id]);

    $event = Event::create([
        'user_id' => $user->id,
        'calendar_id' => $calendar->id,
        'title' => 'Completed event',
        'start_at' => now()->subHour(),
        'end_at' => now()->subMinutes(30),
        'status' => 'completed',
        'last_executed_at' => now()->subHour(),
        'meta' => ['shelly_device_id' => $shelly->id, 'action' => 'on'],
    ]);

    $lastExec = $event->last_executed_at;

    Artisan::call('events:process');

    $event->refresh();
    expect($event->last_executed_at->equalTo($lastExec))->toBeTrue();
});

test('recurring event stays scheduled after execution', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);
    $shelly = ShellyDevice::factory()->create([
        'user_id' => $user->id,
        'ip_address' => '192.168.1.100',
        'model' => 'Shelly Plus 1',
    ]);

    Http::fake([
        '192.168.1.100/rpc/Switch.Set' => Http::response(['was_on' => false], 200),
    ]);

    $event = Event::create([
        'user_id' => $user->id,
        'calendar_id' => $calendar->id,
        'title' => 'Daily lights on',
        'start_at' => now()->subMinute(),
        'end_at' => now()->addHour(),
        'status' => 'scheduled',
        'rrule' => 'FREQ=DAILY;BYHOUR=8',
        'meta' => ['shelly_device_id' => $shelly->id, 'action' => 'on'],
    ]);

    Artisan::call('events:process');

    $event->refresh();
    expect($event->last_executed_at)->not->toBeNull();
    expect($event->status)->toBe('scheduled'); // Still scheduled for next occurrence
});

test('dry run does not execute actions', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);
    $shelly = ShellyDevice::factory()->create(['user_id' => $user->id]);

    Http::fake();

    $event = Event::create([
        'user_id' => $user->id,
        'calendar_id' => $calendar->id,
        'title' => 'Dry run test',
        'start_at' => now()->subMinute(),
        'end_at' => now()->addHour(),
        'status' => 'scheduled',
        'meta' => ['shelly_device_id' => $shelly->id, 'action' => 'on'],
    ]);

    Artisan::call('events:process', ['--dry-run' => true]);

    $event->refresh();
    expect($event->last_executed_at)->toBeNull();
    expect($event->status)->toBe('scheduled');
    Http::assertNothingSent();
});

test('scheduler handles missing shelly device gracefully', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);

    $event = Event::create([
        'user_id' => $user->id,
        'calendar_id' => $calendar->id,
        'title' => 'Missing device',
        'start_at' => now()->subMinute(),
        'end_at' => now()->addHour(),
        'status' => 'scheduled',
        'meta' => ['shelly_device_id' => 99999, 'action' => 'on'],
    ]);

    Artisan::call('events:process');

    $event->refresh();
    expect($event->status)->toBe('scheduled'); // No successful actions = stays scheduled
});

test('scheduler skips events without shelly actions', function () {
    $user = User::factory()->create();
    $calendar = Calendar::factory()->create(['user_id' => $user->id]);

    $event = Event::create([
        'user_id' => $user->id,
        'calendar_id' => $calendar->id,
        'title' => 'No action event',
        'start_at' => now()->subMinute(),
        'end_at' => now()->addHour(),
        'status' => 'scheduled',
        'meta' => [],
    ]);

    Artisan::call('events:process');

    $event->refresh();
    expect($event->last_executed_at)->toBeNull();
    expect($event->status)->toBe('scheduled');
});

test('command reports when no events to process', function () {
    Artisan::call('events:process');
    $output = Artisan::output();

    expect($output)->toContain('No events to process');
});
