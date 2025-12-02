<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $devices = Device::where('user_id', Auth::id())
            ->orderBy('last_seen_at', 'desc')
            ->get();

        return view('livewire.devices.index', [
            'devices' => $devices,
        ]);
    }
}
