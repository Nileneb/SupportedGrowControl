<?php

namespace App\Livewire\Shelly;

use App\Models\ShellyDevice;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

class Index extends Component
{
    public $shellies = [];
    public $showAddForm = false;
    
    // Add form fields
    public string $name = '';
    public string $shellyDeviceId = '';
    public string $ipAddress = '';
    public ?int $linkedDeviceId = null;
    public string $model = '';

    public function mount(): void
    {
        $this->loadShellies();
    }

    public function loadShellies(): void
    {
        $this->shellies = ShellyDevice::where('user_id', auth()->id())
            ->with('device')
            ->orderBy('name')
            ->get();
    }

    public function toggleAddForm(): void
    {
        $this->showAddForm = !$this->showAddForm;
        $this->reset(['name', 'shellyDeviceId', 'ipAddress', 'linkedDeviceId', 'model']);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'shellyDeviceId' => 'required|string|max:255|unique:shelly_devices,shelly_device_id',
            'ipAddress' => 'required|ip',
            'linkedDeviceId' => 'nullable|exists:devices,id',
            'model' => 'nullable|string|max:100',
        ]);

        ShellyDevice::create([
            'user_id' => auth()->id(),
            'device_id' => $validated['linkedDeviceId'],
            'name' => $validated['name'],
            'shelly_device_id' => $validated['shellyDeviceId'],
            'ip_address' => $validated['ipAddress'],
            'model' => $validated['model'],
            'auth_token' => Str::random(32),
        ]);

        $this->loadShellies();
        $this->toggleAddForm();
        
        session()->flash('success', 'Shelly device added successfully');
    }

    public function delete(int $id): void
    {
        $shelly = ShellyDevice::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $shelly->delete();
        
        $this->loadShellies();
        session()->flash('success', 'Shelly device deleted');
    }

    public function turnOn(int $id): void
    {
        $shelly = ShellyDevice::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $result = $shelly->turnOn();
        
        if ($result['success']) {
            session()->flash('success', 'Turned ON: ' . $shelly->name);
        } else {
            session()->flash('error', 'Failed to turn ON: ' . ($result['error'] ?? 'Unknown error'));
        }
        
        $this->loadShellies();
    }

    public function turnOff(int $id): void
    {
        $shelly = ShellyDevice::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        $result = $shelly->turnOff();
        
        if ($result['success']) {
            session()->flash('success', 'Turned OFF: ' . $shelly->name);
        } else {
            session()->flash('error', 'Failed to turn OFF: ' . ($result['error'] ?? 'Unknown error'));
        }
        
        $this->loadShellies();
    }

    public function render()
    {
        return view('livewire.shelly.index');
    }
}
