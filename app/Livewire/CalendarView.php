<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use RRule\RRule;

class CalendarView extends Component
{
    public string $viewMode = 'month'; // month|week|day
    public string $currentDate; // ISO date string (YYYY-MM-DD)
    public ?int $selectedDeviceId = null;
    public ?int $selectedCalendarId = null;

    public array $events = [];
    // Sidebar filters
    public array $devices = [];
    public array $calendars = [];

    public function mount(): void
    {
        $this->currentDate = now()->toDateString();
        // preload simple lists (optional: replace with real queries if models exist)
        if (\Illuminate\Support\Facades\Schema::hasTable('devices')) {
            $this->devices = \App\Models\Device::where('user_id', Auth::id())
                ->orderBy('name')->get(['id','name'])->toArray();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('calendars')) {
            $this->calendars = \App\Models\Calendar::where('user_id', Auth::id())
                ->orderBy('name')->get(['id','name'])->toArray();
        }
        // Avoid crashing if migrations not yet run
        if (\Illuminate\Support\Facades\Schema::hasTable('events')) {
            $this->loadEventsForRange(...$this->rangeForCurrentView());
        } else {
            $this->events = [];
        }
    }

    public function render()
    {
        return view('livewire.calendar-view');
    }

    public function goToPrevious(): void
    {
        $date = Carbon::parse($this->currentDate);
        $date = match ($this->viewMode) {
            'week' => $date->subWeek(),
            'day' => $date->subDay(),
            default => $date->subMonth(),
        };
        $this->currentDate = $date->toDateString();
        $this->loadEventsForRange(...$this->rangeForCurrentView());
    }

    public function goToNext(): void
    {
        $date = Carbon::parse($this->currentDate);
        $date = match ($this->viewMode) {
            'week' => $date->addWeek(),
            'day' => $date->addDay(),
            default => $date->addMonth(),
        };
        $this->currentDate = $date->toDateString();
        $this->loadEventsForRange(...$this->rangeForCurrentView());
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->toDateString();
        $this->loadEventsForRange(...$this->rangeForCurrentView());
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['month', 'week', 'day'], true)) {
            return;
        }
        $this->viewMode = $mode;
        $this->loadEventsForRange(...$this->rangeForCurrentView());
    }

    public function loadEventsForRange(string $start, string $end): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('events')) {
            $this->events = [];
            return;
        }

        // Fetch all user events; we will expand RRULE occurrences within the range
        $query = Event::query()
            ->where('user_id', Auth::id());

        if ($this->selectedDeviceId) {
            $query->where('device_id', $this->selectedDeviceId);
        }
        if ($this->selectedCalendarId) {
            $query->where('calendar_id', $this->selectedCalendarId);
        }

        $startC = Carbon::parse($start);
        $endC = Carbon::parse($end);

        $expanded = [];
        foreach ($query->orderBy('start_at')->get() as $e) {
            // Compute base duration from end_at or meta params
            $durationSeconds = null;
            if ($e->start_at && $e->end_at) {
                $durationSeconds = $e->end_at->diffInSeconds($e->start_at);
            } else {
                $meta = $e->meta ?? [];
                $params = is_array($meta) ? ($meta['params'] ?? []) : [];
                if (isset($params['duration_ms'])) {
                    $durationSeconds = (int) $params['duration_ms'] / 1000;
                }
            }

            if (!empty($e->rrule) && $e->start_at) {
                try {
                    $rr = new RRule($e->rrule, $e->start_at->toDateTimeString());
                    $occ = $rr->getOccurrencesBetween($startC->toDateTimeString(), $endC->toDateTimeString());
                    foreach ($occ as $occurrence) {
                        // $occurrence is DateTime
                        $occStart = Carbon::instance($occurrence);
                        $occEnd = null;
                        if ($durationSeconds) {
                            $occEnd = $occStart->copy()->addSeconds($durationSeconds);
                        }
                        $expanded[] = [
                            'id' => $e->id,
                            'title' => $e->title,
                            'start_at' => $occStart->toIso8601String(),
                            'end_at' => $occEnd?->toIso8601String(),
                            'all_day' => (bool) $e->all_day,
                            'status' => $e->status,
                            'device_id' => $e->device_id,
                            'calendar_id' => $e->calendar_id,
                            'color' => $e->color,
                        ];
                    }
                } catch (\Throwable $ex) {
                    // Fallback: if RRULE fails, include the base event if it intersects the range
                    if ($e->start_at->between($startC, $endC)) {
                        $expanded[] = [
                            'id' => $e->id,
                            'title' => $e->title,
                            'start_at' => $e->start_at?->toIso8601String(),
                            'end_at' => $e->end_at?->toIso8601String(),
                            'all_day' => (bool) $e->all_day,
                            'status' => $e->status,
                            'device_id' => $e->device_id,
                            'calendar_id' => $e->calendar_id,
                            'color' => $e->color,
                        ];
                    }
                }
            } else {
                // Non-recurring: include if within the range
                if ($e->start_at && $e->start_at->between($startC, $endC)) {
                    $expanded[] = [
                        'id' => $e->id,
                        'title' => $e->title,
                        'start_at' => $e->start_at?->toIso8601String(),
                        'end_at' => $e->end_at?->toIso8601String(),
                        'all_day' => (bool) $e->all_day,
                        'status' => $e->status,
                        'device_id' => $e->device_id,
                        'calendar_id' => $e->calendar_id,
                        'color' => $e->color,
                    ];
                }
            }
        }

        $this->events = $expanded;
    }

    public function createEvent(array $data): void
    {
        // Skeleton: emit to EventForm, actual creation handled there
        $this->dispatch('open-event-form', $data);
    }

    public function createEventFromDay(string $date): void
    {
        $this->dispatch('open-event-form', [
            'start_at' => $date.' 00:00:00',
            'end_at' => null,
            'all_day' => false,
            'calendar_id' => $this->selectedCalendarId,
            'device_id' => $this->selectedDeviceId,
        ]);
    }

    public function updateEvent(int $eventId, array $data): void
    {
        // Skeleton: emit to EventForm
        $this->dispatch('open-event-form', array_merge($data, ['id' => $eventId]));
    }

    public function openEvent(int $eventId): void
    {
        $event = Event::where('user_id', Auth::id())->find($eventId);
        if (! $event || ! Auth::user()->can('view', $event)) {
            return;
        }

        $meta = $event->meta ?? [];
        $shelly_device_id = $meta['shelly_device_id'] ?? null;
        $shelly_action = $meta['action'] ?? null;
        $duration_minutes = null;
        if (isset($meta['duration']) && is_numeric($meta['duration'])) {
            $duration_minutes = (int)($meta['duration'] / 60); // Convert seconds to minutes
        }

        $this->dispatch('open-event-form', [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start_at' => $event->start_at?->toDateTimeString(),
            'end_at' => $event->end_at?->toDateTimeString(),
            'all_day' => (bool)$event->all_day,
            'calendar_id' => $event->calendar_id,
            'device_id' => $event->device_id,
            'color' => $event->color,
            'status' => $event->status,
            'rrule' => $event->rrule,
            'shelly_device_id' => $shelly_device_id,
            'shelly_action' => $shelly_action,
            'duration_minutes' => $duration_minutes,
            'last_executed_at' => $event->last_executed_at?->toDateTimeString(),
        ]);
    }

    public function moveEvent(int $eventId, string $newStart, ?string $newEnd): void
    {
        $event = Event::where('user_id', Auth::id())->find($eventId);
        if (! $event || ! Auth::user()->can('reorder', $event)) {
            return;
        }
        $event->start_at = Carbon::parse($newStart);
        $event->end_at = $newEnd ? Carbon::parse($newEnd) : $event->end_at;
        $event->save();
        $this->loadEventsForRange(...$this->rangeForCurrentView());
        $this->dispatch('event-updated', ['id' => $event->id]);
    }

    #[On('event-saved')]
    #[On('event-deleted')]
    #[On('event-updated')]
    public function refreshEvents(): void
    {
        $this->loadEventsForRange(...$this->rangeForCurrentView());
    }

    private function rangeForCurrentView(): array
    {
        $date = Carbon::parse($this->currentDate)->startOfDay();
        return match ($this->viewMode) {
            'week' => [
                $date->copy()->startOfWeek()->toDateTimeString(),
                $date->copy()->endOfWeek()->toDateTimeString(),
            ],
            'day' => [
                $date->copy()->startOfDay()->toDateTimeString(),
                $date->copy()->endOfDay()->toDateTimeString(),
            ],
            default => [
                $date->copy()->startOfMonth()->startOfWeek()->toDateTimeString(),
                $date->copy()->endOfMonth()->endOfWeek()->toDateTimeString(),
            ],
        };
    }
}
