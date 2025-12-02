<div class="max-w-4xl mx-auto">
    <flux:heading size="xl" class="mb-6">Add Actuator to {{ $device->name }}</flux:heading>

    {{-- Progress Steps --}}
    <div class="mb-8">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :active="$currentStep === 1">Select Type</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :active="$currentStep === 2">Configure</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :active="$currentStep === 3">Preview</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    {{-- Step 1: Select Actuator Type --}}
    @if ($currentStep === 1)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Select Actuator Type</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($actuatorTypes->groupBy('category') as $category => $actuators)
                    <div class="space-y-2">
                        <flux:subheading>{{ ucfirst($category) }}</flux:subheading>
                        @foreach ($actuators as $actuatorType)
                            <button
                                type="button"
                                wire:click="selectActuatorType('{{ $actuatorType->id }}')"
                                class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition"
                            >
                                <div class="font-medium">{{ $actuatorType->display_name }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ ucfirst($actuatorType->command_type) }}
                                    @if ($actuatorType->critical)
                                        <flux:badge color="red" size="sm">Critical</flux:badge>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    {{-- Step 2: Configure --}}
    @if ($currentStep === 2)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Configure Actuator</flux:heading>

            @php
                $selectedType = $actuatorTypes->firstWhere('id', $selectedActuatorTypeId);
            @endphp

            @if ($selectedType)
                <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                    <div class="font-medium text-blue-900">{{ $selectedType->display_name }}</div>
                    <div class="text-sm text-blue-700">{{ ucfirst($selectedType->category) }} • {{ ucfirst($selectedType->command_type) }}</div>
                </div>
            @endif

            <div class="space-y-4">
                <flux:input
                    wire:model="pin"
                    label="Pin"
                    placeholder="e.g., 6, GPIO12"
                    required
                />

                <flux:input
                    wire:model="channelKey"
                    label="Channel Key"
                    description="Unique identifier for commands (e.g., main_pump, sprayer)"
                    required
                />

                <flux:input
                    wire:model="minInterval"
                    type="number"
                    label="Min Interval (seconds)"
                    description="Minimum time between activations (0 = no limit)"
                />
            </div>

            <div class="flex gap-3 mt-6">
                <flux:button variant="ghost" wire:click="previousStep">Back</flux:button>
                <flux:button wire:click="nextStep">Next: Preview</flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Step 3: Preview --}}
    @if ($currentStep === 3)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Preview Configuration</flux:heading>

            @php
                $selectedType = $actuatorTypes->firstWhere('id', $selectedActuatorTypeId);
            @endphp

            <dl class="space-y-3">
                <div>
                    <dt class="font-medium text-gray-700">Actuator Type</dt>
                    <dd class="text-gray-900">{{ $selectedType->display_name ?? $selectedActuatorTypeId }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Category</dt>
                    <dd class="text-gray-900">{{ ucfirst($selectedType->category ?? 'custom') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Command Type</dt>
                    <dd class="text-gray-900">{{ ucfirst($selectedType->command_type ?? 'toggle') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Pin</dt>
                    <dd class="text-gray-900">{{ $pin }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Channel Key</dt>
                    <dd class="text-gray-900">{{ $channelKey }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-700">Min Interval</dt>
                    <dd class="text-gray-900">{{ $minInterval ?? 'None' }} seconds</dd>
                </div>
            </dl>

            <div class="flex gap-3 mt-6">
                <flux:button variant="ghost" wire:click="previousStep">Back</flux:button>
                <flux:button wire:click="save">Save Actuator</flux:button>
            </div>
        </flux:card>
    @endif
</div>
