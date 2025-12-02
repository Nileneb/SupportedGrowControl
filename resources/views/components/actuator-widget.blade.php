@props(['actuator', 'device'])

@php
    $type = $actuator['type'] ?? 'unknown';
    $name = $actuator['name'] ?? 'Unknown Actuator';
    $description = $actuator['description'] ?? '';
    $params = $actuator['params'] ?? [];
    
    // Define display config per actuator type
    $config = [
        'pump' => [
            'icon' => '⚙️',
            'color' => 'blue',
            'actionLabel' => 'Run Pump',
            'paramLabel' => 'Duration (ms)',
            'paramName' => 'duration_ms',
            'defaultValue' => 1000,
        ],
        'spray_pump' => [
            'icon' => '💦',
            'color' => 'cyan',
            'actionLabel' => 'Spray',
            'paramLabel' => 'Duration (ms)',
            'paramName' => 'duration_ms',
            'defaultValue' => 500,
        ],
        'fill_valve' => [
            'icon' => '🚰',
            'color' => 'blue',
            'actionLabel' => 'Fill',
            'paramLabel' => 'Duration (ms)',
            'paramName' => 'duration_ms',
            'defaultValue' => 2000,
        ],
        'valve' => [
            'icon' => '🔧',
            'color' => 'indigo',
            'actionLabel' => 'Open Valve',
            'paramLabel' => 'Duration (ms)',
            'paramName' => 'duration_ms',
            'defaultValue' => 1000,
        ],
        'light' => [
            'icon' => '💡',
            'color' => 'yellow',
            'actionLabel' => 'Toggle Light',
            'paramLabel' => 'State',
            'paramName' => 'state',
            'defaultValue' => 'on',
        ],
        'fan' => [
            'icon' => '🌀',
            'color' => 'gray',
            'actionLabel' => 'Run Fan',
            'paramLabel' => 'Speed (%)',
            'paramName' => 'speed',
            'defaultValue' => 100,
        ],
    ];
    
    $c = $config[$type] ?? $config['pump'];
    $actuatorId = $actuator['id'] ?? $actuator['name'];
@endphp

<div class="relative rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4 hover:shadow-lg transition-shadow">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="text-2xl">{{ $c['icon'] }}</span>
            <div>
                <h3 class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $name }}</h3>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ ucfirst(str_replace('_', ' ', $type)) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 bg-gray-400 rounded-full" id="status-{{ $actuatorId }}"></div>
            <span class="text-xs text-gray-500 dark:text-gray-400" id="status-text-{{ $actuatorId }}">Idle</span>
        </div>
    </div>
    
    @if($description)
        <p class="text-sm text-neutral-600 dark:text-neutral-300 mb-3">{{ $description }}</p>
    @endif
    
    <!-- Control Interface -->
    <form class="space-y-3" onsubmit="sendActuatorCommand(event, '{{ $device->public_id }}', '{{ $actuatorId }}', this)">
        @csrf
        
        <!-- Parameter Input -->
        @if(isset($params[$c['paramName']]))
            @php
                $param = $params[$c['paramName']];
                $paramType = $param['type'] ?? 'number';
                $min = $param['min'] ?? 0;
                $max = $param['max'] ?? 10000;
            @endphp
            
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                    {{ $c['paramLabel'] }}
                </label>
                
                @if($paramType === 'boolean' || $c['paramName'] === 'state')
                    <select 
                        name="{{ $c['paramName'] }}" 
                        class="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-700 border border-neutral-300 dark:border-neutral-600 rounded-lg text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="on">On</option>
                        <option value="off">Off</option>
                    </select>
                @else
                    <div class="flex gap-2">
                        <input 
                            type="number" 
                            name="{{ $c['paramName'] }}" 
                            value="{{ $c['defaultValue'] }}"
                            min="{{ $min }}"
                            max="{{ $max }}"
                            class="flex-1 px-3 py-2 bg-neutral-50 dark:bg-neutral-700 border border-neutral-300 dark:border-neutral-600 rounded-lg text-neutral-900 dark:text-neutral-100 focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="flex items-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $min }}-{{ $max }}
                        </span>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Quick Actions (for pumps/valves) -->
        @if(in_array($type, ['pump', 'spray_pump', 'fill_valve', 'valve']))
            <div class="grid grid-cols-3 gap-2">
                <button 
                    type="button" 
                    onclick="setDuration(this.parentElement.parentElement, 500)"
                    class="px-3 py-1.5 text-xs font-medium bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded hover:bg-neutral-200 dark:hover:bg-neutral-600 transition"
                >
                    0.5s
                </button>
                <button 
                    type="button" 
                    onclick="setDuration(this.parentElement.parentElement, 1000)"
                    class="px-3 py-1.5 text-xs font-medium bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded hover:bg-neutral-200 dark:hover:bg-neutral-600 transition"
                >
                    1s
                </button>
                <button 
                    type="button" 
                    onclick="setDuration(this.parentElement.parentElement, 2000)"
                    class="px-3 py-1.5 text-xs font-medium bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded hover:bg-neutral-200 dark:hover:bg-neutral-600 transition"
                >
                    2s
                </button>
            </div>
        @endif
        
        <!-- Action Button -->
        <button 
            type="submit" 
            class="w-full px-4 py-2.5 font-semibold rounded-lg transition-all
                @if($c['color'] === 'blue') bg-blue-600 hover:bg-blue-700 text-white
                @elseif($c['color'] === 'cyan') bg-cyan-600 hover:bg-cyan-700 text-white
                @elseif($c['color'] === 'indigo') bg-indigo-600 hover:bg-indigo-700 text-white
                @elseif($c['color'] === 'yellow') bg-yellow-500 hover:bg-yellow-600 text-black
                @elseif($c['color'] === 'gray') bg-gray-600 hover:bg-gray-700 text-white
                @else bg-blue-600 hover:bg-blue-700 text-white
                @endif
                disabled:opacity-50 disabled:cursor-not-allowed"
        >
            {{ $c['actionLabel'] }}
        </button>
    </form>
    
    <!-- Last Action Info -->
    <div id="last-action-{{ $actuatorId }}" class="mt-3 pt-3 border-t border-neutral-200 dark:border-neutral-700 text-xs text-neutral-500 dark:text-neutral-400 hidden">
        <div class="flex justify-between">
            <span>Last action:</span>
            <span id="last-action-time-{{ $actuatorId }}">-</span>
        </div>
    </div>
</div>

<script>
function setDuration(form, value) {
    const input = form.querySelector('input[name="duration_ms"]');
    if (input) input.value = value;
}

async function sendActuatorCommand(event, deviceId, actuatorId, form) {
    event.preventDefault();
    
    const formData = new FormData(form);
    const params = {};
    for (let [key, value] of formData.entries()) {
        if (key !== '_token') {
            params[key] = isNaN(value) ? value : Number(value);
        }
    }
    
    const button = form.querySelector('button[type="submit"]');
    const statusDot = document.getElementById(`status-${actuatorId}`);
    const statusText = document.getElementById(`status-text-${actuatorId}`);
    const lastAction = document.getElementById(`last-action-${actuatorId}`);
    const lastActionTime = document.getElementById(`last-action-time-${actuatorId}`);
    
    // Update UI to "executing"
    button.disabled = true;
    button.textContent = 'Executing...';
    statusDot.className = 'w-2 h-2 bg-yellow-500 rounded-full animate-pulse';
    statusText.textContent = 'Executing';
    statusText.className = 'text-xs text-yellow-600 dark:text-yellow-400';
    
    try {
        const response = await fetch(`/api/growdash/devices/${deviceId}/commands`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                type: actuatorId,
                params: params
            })
        });
        
        if (!response.ok) throw new Error('Command failed');
        
        const data = await response.json();
        
        // Success
        statusDot.className = 'w-2 h-2 bg-green-500 rounded-full';
        statusText.textContent = 'Success';
        statusText.className = 'text-xs text-green-600 dark:text-green-400';
        
        // Show last action
        lastAction.classList.remove('hidden');
        lastActionTime.textContent = 'Just now';
        
        // Reset after delay
        setTimeout(() => {
            statusDot.className = 'w-2 h-2 bg-gray-400 rounded-full';
            statusText.textContent = 'Idle';
            statusText.className = 'text-xs text-gray-500 dark:text-gray-400';
        }, 3000);
        
    } catch (error) {
        statusDot.className = 'w-2 h-2 bg-red-500 rounded-full';
        statusText.textContent = 'Error';
        statusText.className = 'text-xs text-red-600 dark:text-red-400';
        
        setTimeout(() => {
            statusDot.className = 'w-2 h-2 bg-gray-400 rounded-full';
            statusText.textContent = 'Idle';
            statusText.className = 'text-xs text-gray-500 dark:text-gray-400';
        }, 3000);
    } finally {
        button.disabled = false;
        button.textContent = form.querySelector('button[type="submit"]').dataset.originalText || 'Execute';
    }
}
</script>
