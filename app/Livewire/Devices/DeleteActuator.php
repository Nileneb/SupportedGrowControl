<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use App\Models\DeviceActuator;
use Livewire\Component;

class DeleteActuator extends Component
{
    public Device $device;
    public int $actuatorId;

    public function mount(Device $device, int $actuatorId): void
    {
        $this->device = $device;
        $this->actuatorId = $actuatorId;
    }

    public function delete()
    {
        $actuator = DeviceActuator::where('device_id', $this->device->id)
            ->where('id', $this->actuatorId)
            ->firstOrFail();
        $actuator->delete();
        session()->flash('success', 'Actuator deleted and capabilities updated.');
        return redirect()->route('devices.show', $this->device);
    }

    public function render()
    {
        return view('livewire.devices.delete-actuator');
    }
}
