<div class="space-y-4">
    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Device Information</h3>
        
        <div class="space-y-4">
            <!-- Basic Info -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Device Name</label>
                    <p class="mt-1 text-neutral-900 dark:text-neutral-100">{{ $device->name }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Bootstrap ID</label>
                    <p class="mt-1 font-mono text-sm text-neutral-900 dark:text-neutral-100">{{ $device->bootstrap_id }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Status</label>
                    <p class="mt-1">
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                            @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @else bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                            @endif">
                            {{ ucfirst($device->status) }}
                        </span>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Last Seen</label>
                    <p class="mt-1 text-neutral-900 dark:text-neutral-100">
                        @if($device->last_seen_at)
                            {{ $device->last_seen_at->diffForHumans() }}
                        @else
                            Never
                        @endif
                    </p>
                </div>
            </div>
            
            <!-- Board Info -->
            @if($device->capabilities && isset($device->capabilities['board']))
                <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <h4 class="font-medium text-neutral-900 dark:text-neutral-100 mb-3">Board Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Board Type</label>
                            <p class="mt-1 text-neutral-900 dark:text-neutral-100">
                                {{ $device->capabilities['board']['display_name'] ?? $device->capabilities['board']['id'] ?? 'Unknown' }}
                            </p>
                        </div>
                        
                        @if(isset($device->capabilities['board']['vendor']))
                            <div>
                                <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Vendor</label>
                                <p class="mt-1 text-neutral-900 dark:text-neutral-100">
                                    {{ $device->capabilities['board']['vendor'] }}
                                </p>
                            </div>
                        @endif
                        
                        @if(isset($device->capabilities['board']['mcu']))
                            <div>
                                <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">MCU</label>
                                <p class="mt-1 text-neutral-900 dark:text-neutral-100">
                                    {{ $device->capabilities['board']['mcu'] }}
                                </p>
                            </div>
                        @endif
                        
                        @if(isset($device->capabilities['board']['architecture']))
                            <div>
                                <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Architecture</label>
                                <p class="mt-1 text-neutral-900 dark:text-neutral-100">
                                    {{ $device->capabilities['board']['architecture'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            
            <!-- Capabilities Summary -->
            @if($device->capabilities)
                <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <h4 class="font-medium text-neutral-900 dark:text-neutral-100 mb-3">Capabilities Summary</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ count($device->capabilities['sensors'] ?? []) }}
                            </div>
                            <div class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Sensors</div>
                        </div>
                        
                        <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ count($device->capabilities['actuators'] ?? []) }}
                            </div>
                            <div class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Actuators</div>
                        </div>
                        
                        <div class="text-center p-4 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ isset($device->capabilities['board']['pins']) ? count($device->capabilities['board']['pins']) : 0 }}
                            </div>
                            <div class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Pins</div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Sensor Details -->
            @if($device->capabilities && !empty($device->capabilities['sensors']))
                <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <h4 class="font-medium text-neutral-900 dark:text-neutral-100 mb-3">Configured Sensors</h4>
                    <div class="space-y-2">
                        @foreach($device->capabilities['sensors'] as $sensor)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                                <div>
                                    <p class="font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ $sensor['display_name'] }}
                                    </p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $sensor['category'] ?? 'Sensor' }} 
                                        @if(isset($sensor['pin']))
                                            • Pin {{ $sensor['pin'] }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $sensor['unit'] ?? '' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Actuator Details -->
            @if($device->capabilities && !empty($device->capabilities['actuators']))
                <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <h4 class="font-medium text-neutral-900 dark:text-neutral-100 mb-3">Configured Actuators</h4>
                    <div class="space-y-2">
                        @foreach($device->capabilities['actuators'] as $actuator)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                                <div>
                                    <p class="font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ $actuator['display_name'] }}
                                    </p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ ucfirst($actuator['command_type'] ?? 'control') }}
                                        @if(isset($actuator['pin']))
                                            • Pin {{ $actuator['pin'] }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    @if(isset($actuator['category']))
                                        {{ ucfirst($actuator['category']) }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Timestamps -->
            <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                <h4 class="font-medium text-neutral-900 dark:text-neutral-100 mb-3">Timestamps</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Created</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $device->created_at->format('Y-m-d H:i:s') }}
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Last Updated</label>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $device->updated_at->format('Y-m-d H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                <h4 class="font-medium text-neutral-900 dark:text-neutral-100 mb-3">Actions</h4>
                <div class="flex gap-2">
                    <a href="/devices/{{ $device->id }}/edit" 
                       class="inline-flex items-center px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-600">
                        Edit Device
                    </a>
                    
                    <button 
                        onclick="refreshCapabilities()"
                        class="inline-flex items-center px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-600">
                        Refresh Capabilities
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function refreshCapabilities() {
        try {
            const response = await fetch(`/api/devices/{{ $device->id }}/refresh-capabilities`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            if (response.ok) {
                alert('Capabilities refresh requested. The device will update on its next connection.');
            } else {
                alert('Failed to request capabilities refresh');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
</script>
