<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

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

        $query = Event::query()
            ->where('user_id', Auth::id())
            ->where('start_at', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $start);
            });

        if ($this->selectedDeviceId) {
            $query->where('device_id', $this->selectedDeviceId);
        }
        if ($this->selectedCalendarId) {
            $query->where('calendar_id', $this->selectedCalendarId);
        }

        $this->events = $query->orderBy('start_at')->get()->map(function (Event $e) {
            return [
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
        })->toArray();
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
