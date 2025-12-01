<?php

use App\Models\Device;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $bootstrapCode = '';
    public string $errorMessage = '';
    public string $successMessage = '';

    public function pair(): void
    {
        $this->validate([
            'bootstrapCode' => 'required|string|size:6',
        ]);

        $device = Device::findByBootstrapCode($this->bootstrapCode);

        if (!$device) {
            $this->errorMessage = 'Invalid pairing code. Please check the code and try again.';
            $this->successMessage = '';
            return;
        }

        if ($device->isPaired()) {
            $this->errorMessage = 'This device is already paired.';
            $this->successMessage = '';
            return;
        }

        // Pair device with current user
        $plaintextToken = $device->pairWithUser(Auth::id());

        $this->successMessage = "Device '{$device->name}' paired successfully!";
        $this->errorMessage = '';
        $this->bootstrapCode = '';

        // Redirect to device list after success
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="max-w-2xl mx-auto">
    <flux:card>
        <flux:heading size="lg">Pair New Device</flux:heading>
        <flux:subheading>Enter the 6-digit pairing code shown on your device.</flux:subheading>

        <form wire:submit="pair" class="space-y-6 mt-6">
            <flux:input
                wire:model="bootstrapCode"
                label="Pairing Code"
                placeholder="ABC123"
                maxlength="6"
                class="text-center text-2xl tracking-widest uppercase"
                autofocus
            />

            @if($errorMessage)
                <flux:badge variant="danger" class="w-full">
                    {{ $errorMessage }}
                </flux:badge>
            @endif

            @if($successMessage)
                <flux:badge variant="success" class="w-full">
                    {{ $successMessage }}
                </flux:badge>
            @endif

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary" class="flex-1">
                    Pair Device
                </flux:button>
                <flux:button
                    type="button"
                    variant="ghost"
                    href="{{ route('dashboard') }}"
                    wire:navigate
                >
                    Cancel
                </flux:button>
            </div>
        </form>

        <flux:separator class="my-6" />

        <div class="prose prose-sm">
            <h3>How to pair your device:</h3>
            <ol>
                <li>Run the pairing script on your device (e.g., <code>python pairing.py</code>)</li>
                <li>The device will display a 6-digit code</li>
                <li>Enter that code above and click "Pair Device"</li>
                <li>Your device will automatically connect within a few seconds</li>
            </ol>
        </div>
    </flux:card>
</div>
