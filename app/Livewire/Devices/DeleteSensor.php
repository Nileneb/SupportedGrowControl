<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use App\Models\DeviceSensor;
use Livewire\Component;

class DeleteSensor extends Component
{
    public Device $device;
    public int $sensorId;

    public function mount(Device $device, int $sensorId): void
    {
        $this->device = $device;
        $this->sensorId = $sensorId;
    }

    public function delete()
    {
        $sensor = DeviceSensor::where('device_id', $this->device->id)
            ->where('id', $this->sensorId)
            ->firstOrFail();
        $sensor->delete();
        $this->device->syncCapabilitiesFromInstances();
        session()->flash('success', 'Sensor deleted and capabilities updated.');
        return redirect()->route('devices.show', $this->device);
    }

    public function render()
    {
        return view('livewire.devices.delete-sensor');
    }
}
