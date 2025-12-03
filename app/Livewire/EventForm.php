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
    public string $status = 'planned';

    public function render()
    {
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
            'status' => ['required', Rule::in(['planned','active','done','canceled'])],
        ]);

        if ($this->id) {
            $event = Event::where('user_id', Auth::id())->find($this->id);
            if (! $event || ! Auth::user()->can('update', $event)) {
                return;
            }
            $event->update($validated);
            $this->dispatch('event-updated', ['id' => $event->id]);
        } else {
            $event = Event::create(array_merge($validated, [
                'user_id' => Auth::id(),
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
