<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use App\Models\DeviceSensor;
use App\Models\SensorType;
use Livewire\Component;
use Livewire\Attributes\On;

class AddSensor extends Component
{
    // Wizard state
    public int $currentStep = 1;
    public Device $device;

    // Step 1: Select sensor type
    public ?string $selectedSensorTypeId = null;

    // Step 2: Configure
    public string $pin = '';
    public string $channelKey = '';
    public ?int $minInterval = null;
    public bool $critical = false;
    public array $config = [];

    // Available sensor types from catalog
    public $sensorTypes = [];

    public function mount(Device $device): void
    {
        $this->device = $device;
        $this->sensorTypes = SensorType::orderBy('category')->orderBy('display_name')->get();
    }

    public function selectSensorType(string $sensorTypeId): void
    {
        $this->selectedSensorTypeId = $sensorTypeId;
        
        // Auto-populate channel_key suggestion
        $sensorType = SensorType::find($sensorTypeId);
        if ($sensorType) {
            $this->channelKey = $sensorType->id;
            $this->minInterval = $sensorType->meta['min_interval'] ?? 10;
            $this->critical = $sensorType->critical ?? false;
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

        DeviceSensor::create([
            'device_id' => $this->device->id,
            'sensor_type_id' => $this->selectedSensorTypeId,
            'pin' => $this->pin,
            'channel_key' => $this->channelKey,
            'min_interval' => $this->minInterval,
            'critical' => $this->critical,
            'config' => $this->config,
        ]);

        // Rebuild capabilities JSON for agent
        $this->device->syncCapabilitiesFromInstances();

        session()->flash('success', 'Sensor added successfully');
        
        return $this->redirect(route('devices.show', $this->device), navigate: true);
    }

    public function rules(): array
    {
        return [
            'selectedSensorTypeId' => 'required|exists:sensor_types,id',
            'pin' => 'required|string|max:20',
            'channelKey' => 'required|string|max:50|unique:device_sensors,channel_key,NULL,id,device_id,' . $this->device->id,
            'minInterval' => 'nullable|integer|min:1',
            'critical' => 'boolean',
        ];
    }

    public function render()
    {
        return view('livewire.devices.add-sensor');
    }
}
