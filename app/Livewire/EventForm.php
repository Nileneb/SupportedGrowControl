<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EventForm extends Component
{
    public bool $open = false;
    public ?int $id = null;

    public string $title = '';
    public ?string $description = null;
    public ?string $start_at = null; // ISO string
    public ?string $end_at = null;   // ISO string
    public bool $all_day = false;
    public ?int $calendar_id = null;
    public ?int $device_id = null;
    public ?string $color = null;
    public string $status = 'scheduled';
    public ?string $last_executed_at = null;

    // Scheduling & command linkage
    public ?string $rrule = null; // e.g., FREQ=DAILY;INTERVAL=2
    public ?int $shelly_device_id = null; // Shelly device target
    public ?string $shelly_action = null; // on|off|toggle
    public ?int $duration_minutes = null; // duration for auto-off

    // Device list for dropdown
    public array $devices = [];
    public array $shellyDevices = [];

    public function render()
    {
        // preload user devices for selection
        if (\Illuminate\Support\Facades\Schema::hasTable('devices')) {
            $this->devices = \App\Models\Device::where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->orderBy('name')->get(['id','name'])->toArray();
        } else {
            $this->devices = [];
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('shelly_devices')) {
            $this->shellyDevices = \App\Models\ShellyDevice::where('user_id', Auth::id())
                ->orderBy('name')->get(['id','name','ip_address'])->toArray();
        } else {
            $this->shellyDevices = [];
        }

        return view('livewire.event-form');
    }

    #[On('open-event-form')]
    public function open(array $data = []): void
    {
        $this->fill($data);
        $this->open = true;
    }

    public function close(): void
    {
        $this->reset('open');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'all_day' => ['boolean'],
            'calendar_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:32'],
            'status' => ['required', Rule::in(['scheduled','completed','canceled'])],
            'rrule' => ['nullable', 'string', 'max:255'],
            'shelly_device_id' => ['nullable', 'integer'],
            'shelly_action' => ['nullable', Rule::in(['on','off','toggle'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        // Build meta for Shelly actions (aligned with scheduler)
        $meta = null;
        if (!empty($validated['shelly_device_id']) && !empty($validated['shelly_action'])) {
            $meta = [
                'shelly_device_id' => $validated['shelly_device_id'],
                'action' => $validated['shelly_action'],
            ];
            if (!empty($validated['duration_minutes'])) {
                $meta['duration'] = (int)$validated['duration_minutes'] * 60; // seconds
            }
        }

        if ($this->id) {
            $event = Event::where('user_id', Auth::id())->find($this->id);
            if (! $event || ! Auth::user()->can('update', $event)) {
                return;
            }
            $event->update(array_merge($validated, [
                'rrule' => $validated['rrule'] ?? $event->rrule,
                'meta' => $meta ?? $event->meta,
            ]));
            $this->dispatch('event-updated', ['id' => $event->id]);
        } else {
            $event = Event::create(array_merge($validated, [
                'user_id' => Auth::id(),
                'rrule' => $validated['rrule'] ?? null,
                'meta' => $meta,
                'status' => $validated['status'] ?? 'scheduled',
            ]));
            $this->dispatch('event-saved', ['id' => $event->id]);
        }

        $this->open = false;
    }

    public function delete(): void
    {
        if (! $this->id) {
            return;
        }
        $event = Event::where('user_id', Auth::id())->find($this->id);
        if (! $event || ! Auth::user()->can('delete', $event)) {
            return;
        }
        $event->delete();
        $this->dispatch('event-deleted', ['id' => $this->id]);
        $this->open = false;
    }
}
