<?php

use App\Models\Calendar;
use App\Models\Event;
use App\Models\ShellyDevice;
use App\Models\User;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->device = Device::factory()->create(['user_id' => $this->user->id]);
    $this->shelly = ShellyDevice::factory()->create(['user_id' => $this->user->id]);
    $this->calendar = Calendar::factory()->create(['user_id' => $this->user->id]);
});

// ==================== Calendar Event Tests ====================

test('user can create calendar event', function () {
    $event = Event::create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'title' => 'Turn on lights',
        'description' => 'Morning routine',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHour(),
        'all_day' => false,
    ]);

    expect($event->user_id)->toBe($this->user->id);
    expect($event->calendar_id)->toBe($this->calendar->id);
    expect($event->title)->toBe('Turn on lights');
});

test('event belongs to calendar', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
    ]);

    expect($event->calendar)->toBeInstanceOf(Calendar::class);
    expect($event->calendar->id)->toBe($this->calendar->id);
});

test('calendar has many events', function () {
    Event::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
    ]);

    expect($this->calendar->events()->count())->toBe(3);
});

// ==================== Event-Device Linking Tests ====================

test('event can be linked to grow device', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'device_id' => $this->device->id,
        'title' => 'Water plants at 8am',
    ]);

    expect($event->device)->toBeInstanceOf(Device::class);
    expect($event->device->id)->toBe($this->device->id);
});

test('event stores shelly action in meta', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'meta' => [
            'shelly_device_id' => $this->shelly->id,
            'action' => 'on',
            'duration' => 300, // 5 minutes
        ],
    ]);

    expect($event->meta['shelly_device_id'])->toBe($this->shelly->id);
    expect($event->meta['action'])->toBe('on');
    expect($event->meta['duration'])->toBe(300);
});

test('event stores multiple shelly actions', function () {
    $shelly2 = ShellyDevice::factory()->create(['user_id' => $this->user->id]);

    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'meta' => [
            'shelly_actions' => [
                ['device_id' => $this->shelly->id, 'action' => 'on'],
                ['device_id' => $shelly2->id, 'action' => 'off'],
            ],
        ],
    ]);

    expect($event->meta['shelly_actions'])->toHaveCount(2);
    expect($event->meta['shelly_actions'][0]['action'])->toBe('on');
    expect($event->meta['shelly_actions'][1]['action'])->toBe('off');
});

// ==================== Recurring Event Tests ====================

test('event supports rrule for recurring schedules', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'title' => 'Daily light control',
        'rrule' => 'FREQ=DAILY;BYHOUR=8',
        'start_at' => now()->setTime(8, 0),
        'end_at' => now()->setTime(8, 0)->addMinutes(5),
    ]);

    expect($event->rrule)->toBe('FREQ=DAILY;BYHOUR=8');
});

test('event tracks last execution time', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'last_executed_at' => null,
    ]);

    expect($event->last_executed_at)->toBeNull();

    $event->update(['last_executed_at' => now()]);
    $event->refresh();

    expect($event->last_executed_at)->not->toBeNull();
});

// ==================== All-Day Event Tests ====================

test('event supports all day flag', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'all_day' => true,
        'start_at' => now()->startOfDay(),
    ]);

    expect($event->all_day)->toBeTrue();
});

// ==================== Event Duration Tests ====================

test('event calculates duration', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'start_at' => now(),
        'end_at' => now()->addHours(2),
    ]);

    expect($event->duration)->toBe(7200); // 2 hours in seconds
});

test('event duration is null when no end time', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'start_at' => now(),
        'end_at' => null,
    ]);

    expect($event->duration)->toBeNull();
});

// ==================== Event Color Tests ====================

test('event stores custom color', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'color' => '#FF5733',
    ]);

    expect($event->color)->toBe('#FF5733');
});

// ==================== Event Status Tests ====================

test('event supports status field', function () {
    $event = Event::factory()->create([
        'user_id' => $this->user->id,
        'calendar_id' => $this->calendar->id,
        'status' => 'scheduled',
    ]);

    expect($event->status)->toBe('scheduled');

    $event->update(['status' => 'completed']);
    expect($event->status)->toBe('completed');
});
