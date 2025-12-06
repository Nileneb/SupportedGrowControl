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

    // Scheduling & command linkage
    public ?string $rrule = null; // e.g., FREQ=DAILY;INTERVAL=2
    public ?string $command_type = null;
    public array $command_params = [];
    
    // Available commands for selected device
    public array $availableCommands = [];
    public array $paramTemplate = [];

    // Device list for dropdown
    public array $devices = [];

    public function render()
    {
        // preload user devices for selection
        if (\Illuminate\Support\Facades\Schema::hasTable('devices')) {
            $this->devices = \App\Models\Device::where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->orderBy('name')->get(['id','name'])->toArray();
        } else {
            $this->devices = [];
        }
        return view('livewire.event-form');
    }

    #[On('open-event-form')]
    public function open(array $data = []): void
    {
        $this->fill($data);
        
        // Load available commands if device is selected
        if ($this->device_id) {
            $this->loadDeviceCommands();
        }
        
        $this->open = true;
    }

    public function updatedDeviceId(): void
    {
        $this->loadDeviceCommands();
        $this->command_type = null;
        $this->command_params = [];
        $this->paramTemplate = [];
    }

    public function updatedCommandType(): void
    {
        $this->command_params = [];
        
        // Find selected command and load parameter template
        $selected = collect($this->availableCommands)->firstWhere('command_type', $this->command_type);
        $this->paramTemplate = $selected['params_template'] ?? [];
        
        // Initialize default values
        foreach ($this->paramTemplate as $param) {
            if (isset($param['default'])) {
                $this->command_params[$param['name']] = $param['default'];
            }
        }
    }

    private function loadDeviceCommands(): void
    {
        if (!$this->device_id) {
            $this->availableCommands = [];
            return;
        }

        $device = \App\Models\Device::find($this->device_id);
        if (!$device) {
            $this->availableCommands = [];
            return;
        }

        // Fetch from DeviceActuatorController logic
        $capabilities = $device->capabilities ?? [];
        $actuators = $capabilities['actuators'] ?? [];

        $commands = [];

        foreach ($actuators as $actuator) {
            $commands[] = [
                'command_type' => 'serial_command',
                'label' => $actuator['display_name'] ?? $actuator['id'],
                'actuator_id' => $actuator['id'],
                'params_template' => $this->getActuatorParamsTemplate($actuator),
            ];
        }

        // Generic commands
        $commands[] = [
            'command_type' => 'serial_command',
            'label' => 'Custom Serial Command',
            'params_template' => [
                ['name' => 'command', 'type' => 'text', 'required' => true, 'label' => 'Command Text'],
            ],
        ];

        $this->availableCommands = $commands;
    }

    private function getActuatorParamsTemplate(array $actuator): array
    {
        $template = [];
        $commandType = $actuator['command_type'] ?? 'toggle';

        switch ($commandType) {
            case 'duration':
                $template[] = [
                    'name' => 'duration_ms',
                    'type' => 'number',
                    'required' => true,
                    'label' => 'Duration (ms)',
                    'default' => 1000,
                ];
                break;

            case 'toggle':
                $template[] = [
                    'name' => 'state',
                    'type' => 'select',
                    'required' => true,
                    'label' => 'State',
                    'options' => ['on', 'off'],
                    'default' => 'on',
                ];
                break;

            case 'value':
                $params = $actuator['params'] ?? [];
                foreach ($params as $param) {
                    $template[] = [
                        'name' => $param['name'],
                        'type' => $param['type'] === 'int' ? 'number' : 'text',
                        'required' => true,
                        'label' => ucfirst($param['name']),
                        'min' => $param['min'] ?? null,
                        'max' => $param['max'] ?? null,
                    ];
                }
                break;
        }

        return $template;
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
            'rrule' => ['nullable', 'string', 'max:255'],
            'command_type' => ['nullable', 'string', 'max:50'],
            'command_params' => ['nullable', 'array'],
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
