<div class="space-y-6">
    {{-- Alert Messages --}}
    @if ($successMessage)
        <flux:banner variant="success" class="mb-4">
            {{ $successMessage }}
        </flux:banner>
    @endif

    @if ($errorMessage)
        <flux:banner variant="danger" class="mb-4">
            {{ $errorMessage }}
        </flux:banner>
    @endif

    @if (!$capabilities)
        <flux:card>
            <p class="text-gray-500">Device capabilities not yet configured. Please update device firmware or sync capabilities from agent.</p>
        </flux:card>
    @else
        {{-- Category Tabs --}}
        @if (count($categories) > 1)
            <flux:tabs wire:model.live="activeCategory" class="mb-4">
                @foreach ($categories as $category)
                    <flux:tab name="{{ $category }}">
                        {{ ucfirst($category) }}
                    </flux:tab>
                @endforeach
            </flux:tabs>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Actuator Controls --}}
            <div class="space-y-4">
                <flux:heading size="lg">Available Controls</flux:heading>

                @php
                    $actuators = $actuatorsByCategory[$activeCategory] ?? [];
                @endphp

                @forelse ($actuators as $actuator)
                    <flux:card>
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <flux:heading size="md">{{ $actuator->display_name }}</flux:heading>
                                <p class="text-sm text-gray-500">{{ ucfirst($actuator->category) }} • {{ ucfirst($actuator->command_type) }}</p>
                            </div>
                            @if ($actuator->critical)
                                <flux:badge color="red">Critical</flux:badge>
                            @endif
                        </div>

                        @if ($selectedActuator === $actuator->id)
                            {{-- Command Form --}}
                            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                @foreach ($actuator->params as $param)
                                    <flux:field class="mb-3">
                                        <flux:label>
                                            {{ ucfirst(str_replace('_', ' ', $param->name)) }}
                                            @if ($param->unit)
                                                <span class="text-xs text-gray-500">({{ $param->unit }})</span>
                                            @endif
                                        </flux:label>

                                        @if ($param->type === 'int' || $param->type === 'float')
                                            <flux:input 
                                                type="number" 
                                                wire:model="commandParams.{{ $param->name }}"
                                                min="{{ $param->min }}"
                                                max="{{ $param->max }}"
                                                step="{{ $param->type === 'float' ? '0.1' : '1' }}"
                                            />
                                            @if ($param->min !== null || $param->max !== null)
                                                <flux:description>Range: {{ $param->min ?? 'any' }} - {{ $param->max ?? 'any' }}</flux:description>
                                            @endif
                                        @elseif ($param->type === 'bool')
                                            <flux:checkbox wire:model="commandParams.{{ $param->name }}" />
                                        @else
                                            <flux:input 
                                                type="text" 
                                                wire:model="commandParams.{{ $param->name }}"
                                            />
                                        @endif
                                    </flux:field>
                                @endforeach

                                <div class="flex gap-2 mt-4">
                                    <flux:button 
                                        variant="primary" 
                                        wire:click="sendCommand"
                                        :disabled="$device->status !== 'online'"
                                    >
                                        Send Command
                                    </flux:button>
                                    <flux:button variant="ghost" wire:click="cancelCommand">
                                        Cancel
                                    </flux:button>
                                </div>

                                @if ($device->status !== 'online')
                                    <p class="text-xs text-red-600 mt-2">Device must be online to send commands</p>
                                @endif
                            </div>
                        @else
                            {{-- Quick Action Button --}}
                            <flux:button 
                                class="mt-3" 
                                wire:click="selectActuator('{{ $actuator->id }}')"
                                :disabled="$device->status !== 'online'"
                            >
                                Configure & Execute
                            </flux:button>
                        @endif

                        @if ($actuator->min_interval)
                            <p class="text-xs text-gray-500 mt-2">Minimum interval: {{ $actuator->min_interval }}s</p>
                        @endif
                    </flux:card>
                @empty
                    <p class="text-gray-500">No actuators available in this category.</p>
                @endforelse
            </div>

            {{-- Recent Commands --}}
            <div class="space-y-4">
                <flux:heading size="lg">Recent Commands</flux:heading>

                @forelse ($recentCommands as $command)
                    <flux:card>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $command->type)) }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $command->created_at->diffForHumans() }}
                                    @if ($command->createdBy)
                                        by {{ $command->createdBy->name }}
                                    @endif
                                </p>

                                @if ($command->params && count($command->params) > 0)
                                    <div class="mt-2 text-xs text-gray-600">
                                        @foreach ($command->params as $key => $value)
                                            <span class="inline-block mr-2">{{ $key }}: {{ $value }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($command->result_message)
                                    <p class="mt-1 text-sm text-gray-700">{{ $command->result_message }}</p>
                                @endif
                            </div>

                            <div>
                                @if ($command->status === 'completed')
                                    <flux:badge color="green">Completed</flux:badge>
                                @elseif ($command->status === 'failed')
                                    <flux:badge color="red">Failed</flux:badge>
                                @elseif ($command->status === 'executing')
                                    <flux:badge color="yellow">Executing</flux:badge>
                                @else
                                    <flux:badge color="gray">Pending</flux:badge>
                                @endif
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <p class="text-gray-500">No commands sent yet.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
