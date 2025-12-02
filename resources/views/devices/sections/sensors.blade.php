<div class="space-y-4">
    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Sensor Readings</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($sensors as $sensor)
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-700/30 p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h4 class="font-medium text-neutral-900 dark:text-neutral-100">
                                {{ $sensor['display_name'] }}
                            </h4>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $sensor['category'] ?? 'Sensor' }}
                            </p>
                        </div>
                        <div class="h-2 w-2 rounded-full bg-green-500" 
                             id="sensor-status-{{ $sensor['id'] }}"
                             title="Active"></div>
                    </div>
                    
                    <div class="mt-4">
                        <div id="sensor-value-{{ $sensor['id'] }}" 
                             class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                            @if(isset($sensorReadings[$sensor['id']]))
                                {{ number_format($sensorReadings[$sensor['id']]['value'], 1) }}
                            @else
                                --
                            @endif
                        </div>
                        <div class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                            {{ $sensor['unit'] ?? '' }}
                        </div>
                    </div>
                    
                    @if(isset($sensorReadings[$sensor['id']]))
                        <div id="sensor-timestamp-{{ $sensor['id'] }}" 
                             class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">
                            Updated {{ $sensorReadings[$sensor['id']]['timestamp']->diffForHumans() }}
                        </div>
                    @else
                        <div id="sensor-timestamp-{{ $sensor['id'] }}" 
                             class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">
                            No data yet
                        </div>
                    @endif
                    
                    @if(isset($sensor['min_value']) || isset($sensor['max_value']))
                        <div class="mt-3 pt-3 border-t border-neutral-200 dark:border-neutral-600">
                            <div class="flex justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                @if(isset($sensor['min_value']))
                                    <span>Min: {{ $sensor['min_value'] }}{{ $sensor['unit'] ?? '' }}</span>
                                @endif
                                @if(isset($sensor['max_value']))
                                    <span>Max: {{ $sensor['max_value'] }}{{ $sensor['unit'] ?? '' }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        @if(empty($sensors))
            <div class="text-center py-12">
                <p class="text-neutral-500 dark:text-neutral-400">No sensors configured for this device</p>
            </div>
        @endif
    </div>
</div>

<script>
    const deviceId = {{ $device->id }};
    const sensorIds = @json(array_column($sensors, 'id'));

    // Subscribe to device telemetry
    if (window.Echo) {
        window.Echo.private(`device.${deviceId}`)
            .listen('DeviceTelemetryReceived', (event) => {
                updateSensorReadings(event.telemetry);
            });
    }

    // Update sensor readings from WebSocket
    function updateSensorReadings(telemetry) {
        if (!telemetry.sensors) return;
        
        Object.entries(telemetry.sensors).forEach(([sensorId, data]) => {
            if (!sensorIds.includes(sensorId)) return;
            
            const valueElement = document.getElementById(`sensor-value-${sensorId}`);
            const timestampElement = document.getElementById(`sensor-timestamp-${sensorId}`);
            const statusElement = document.getElementById(`sensor-status-${sensorId}`);
            
            if (valueElement && data.value !== undefined) {
                // Animate value change
                valueElement.style.transition = 'color 0.3s';
                valueElement.style.color = 'rgb(59, 130, 246)'; // blue
                valueElement.textContent = typeof data.value === 'number' 
                    ? data.value.toFixed(1) 
                    : data.value;
                
                setTimeout(() => {
                    valueElement.style.color = '';
                }, 300);
            }
            
            if (timestampElement) {
                timestampElement.textContent = 'Just now';
            }
            
            if (statusElement) {
                statusElement.className = 'h-2 w-2 rounded-full bg-green-500 animate-pulse';
                setTimeout(() => {
                    statusElement.className = 'h-2 w-2 rounded-full bg-green-500';
                }, 1000);
            }
        });
    }

    // Periodically update timestamps
    setInterval(() => {
        sensorIds.forEach(sensorId => {
            const timestampElement = document.getElementById(`sensor-timestamp-${sensorId}`);
            if (timestampElement && !timestampElement.textContent.includes('No data')) {
                // This would need server time sync in production
                // For now, just mark as "X minutes ago" getting older
            }
        });
    }, 60000); // Every minute
</script>
