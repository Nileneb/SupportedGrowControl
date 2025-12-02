<?php

namespace App\Livewire\Devices;

use App\DTOs\DeviceCapabilities;
use App\Models\Command;
use App\Models\Device;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Commands extends Component
{
    public Device $device;
    public ?DeviceCapabilities $capabilities = null;
    public array $categories = [];
    public string $activeCategory = '';
    public ?string $selectedActuator = null;
    public array $commandParams = [];
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(Device $device)
    {
        $this->device = $device;

        if ($device->capabilities) {
            try {
                $this->capabilities = DeviceCapabilities::fromArray($device->capabilities);
                $this->categories = $this->capabilities->getAllCategories();
                $this->activeCategory = $this->categories[0] ?? '';
            } catch (\Exception $e) {
                $this->errorMessage = 'Failed to load device capabilities';
            }
        }
    }

    public function selectActuator(string $actuatorId)
    {
        $this->selectedActuator = $actuatorId;
        $this->commandParams = [];
        $this->successMessage = null;
        $this->errorMessage = null;

        // Initialize params with default values
        $actuator = $this->capabilities?->getActuatorById($actuatorId);
        if ($actuator) {
            foreach ($actuator->params as $param) {
                $this->commandParams[$param->name] = $param->min ?? null;
            }
        }
    }

    public function switchCategory(string $category)
    {
        $this->activeCategory = $category;
        $this->selectedActuator = null;
        $this->commandParams = [];
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function sendCommand()
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (!$this->selectedActuator) {
            $this->errorMessage = 'No actuator selected';
            return;
        }

        $actuator = $this->capabilities?->getActuatorById($this->selectedActuator);
        if (!$actuator) {
            $this->errorMessage = 'Actuator not found';
            return;
        }

        // Validate params
        $errors = $actuator->validateParams($this->commandParams);
        if (!empty($errors)) {
            $this->errorMessage = 'Invalid parameters: ' . implode(', ', $errors);
            return;
        }

        // Check device status
        if ($this->device->status !== 'online') {
            $this->errorMessage = 'Device is not online';
            return;
        }

        // Create command
        try {
            Command::create([
                'device_id' => $this->device->id,
                'created_by_user_id' => Auth::id(),
                'type' => $this->selectedActuator,
                'params' => $this->commandParams,
                'status' => 'pending',
            ]);

            $this->successMessage = "Command '{$actuator->display_name}' sent successfully";
            $this->selectedActuator = null;
            $this->commandParams = [];
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to send command';
        }
    }

    public function cancelCommand()
    {
        $this->selectedActuator = null;
        $this->commandParams = [];
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        $actuatorsByCategory = [];

        if ($this->capabilities) {
            foreach ($this->categories as $category) {
                $actuatorsByCategory[$category] = $this->capabilities->getActuatorsByCategory($category);
            }
        }

        $recentCommands = Command::where('device_id', $this->device->id)
            ->with('createdBy:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('livewire.devices.commands', [
            'actuatorsByCategory' => $actuatorsByCategory,
            'recentCommands' => $recentCommands,
        ]);
    }
}
