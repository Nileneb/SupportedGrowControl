<?php

namespace App\Livewire;

use App\Models\Device;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DevicePairing extends Component
{
    public string $bootstrapCode = '';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function pair()
    {
        $this->validate([
            'bootstrapCode' => 'required|string|size:6',
        ]);

        $this->reset(['successMessage', 'errorMessage']);

        $device = Device::findByBootstrapCode(strtoupper($this->bootstrapCode));

        if (!$device) {
            $this->errorMessage = 'Invalid bootstrap code or device already paired.';
            return;
        }

        $device->pairWithUser(Auth::id());

        $this->successMessage = "Device '{$device->name}' paired successfully!";
        $this->bootstrapCode = '';

        $this->dispatch('device-paired', deviceId: $device->id);
    }

    public function render()
    {
        $unclaimedDevices = Device::unclaimed()
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('livewire.device-pairing', [
            'unclaimedDevices' => $unclaimedDevices,
        ]);
    }
}
