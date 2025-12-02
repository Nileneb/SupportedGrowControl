<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use App\Models\DeviceActuator;
use App\Models\ActuatorType;
use Livewire\Component;

class AddActuator extends Component
{
    // Wizard state
    public int $currentStep = 1;
    public Device $device;

    // Step 1: Select actuator type
    public ?string $selectedActuatorTypeId = null;

    // Step 2: Configure
    public string $pin = '';
    public string $channelKey = '';
    public ?int $minInterval = null;
    public array $config = [];

    // Available actuator types from catalog
    public $actuatorTypes = [];

    public function mount(Device $device): void
    {
        $this->device = $device;
        $this->actuatorTypes = ActuatorType::orderBy('category')->orderBy('display_name')->get();
    }

    public function selectActuatorType(string $actuatorTypeId): void
    {
        $this->selectedActuatorTypeId = $actuatorTypeId;
        
        // Auto-populate channel_key suggestion
        $actuatorType = ActuatorType::find($actuatorTypeId);
        if ($actuatorType) {
            $this->channelKey = $actuatorType->id;
            $this->minInterval = $actuatorType->min_interval ?? 0;
        }

        $this->currentStep = 2;
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function nextStep(): void
    {
        $this->validate();
        
        if ($this->currentStep === 2) {
            $this->currentStep = 3; // Preview
        }
    }

    public function save(): void
    {
        $this->validate();

        DeviceActuator::create([
            'device_id' => $this->device->id,
            'actuator_type_id' => $this->selectedActuatorTypeId,
            'pin' => $this->pin,
            'channel_key' => $this->channelKey,
            'min_interval' => $this->minInterval,
            'config' => $this->config,
        ]);

        // Rebuild capabilities JSON for agent
        $this->device->syncCapabilitiesFromInstances();

        session()->flash('success', 'Actuator added successfully');
        
        return $this->redirect(route('devices.show', $this->device), navigate: true);
    }

    public function rules(): array
    {
        return [
            'selectedActuatorTypeId' => 'required|exists:actuator_types,id',
            'pin' => 'required|string|max:20',
            'channelKey' => 'required|string|max:50|unique:device_actuators,channel_key,NULL,id,device_id,' . $this->device->id,
            'minInterval' => 'nullable|integer|min:0',
        ];
    }

    public function render()
    {
        return view('livewire.devices.add-actuator');
    }
}
