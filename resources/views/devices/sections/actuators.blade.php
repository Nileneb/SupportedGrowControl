<div class="space-y-4">
    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Actuator Controls</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($actuators as $actuator)
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-700/30 p-4">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-medium text-neutral-900 dark:text-neutral-100">
                                {{ $actuator['display_name'] }}
                            </h4>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ ucfirst($actuator['command_type'] ?? 'control') }}
                            </p>
                        </div>
                        <div id="actuator-status-{{ $actuator['id'] }}" 
                             class="h-2 w-2 rounded-full bg-neutral-400"
                             title="Idle"></div>
                    </div>
                    
                    @if(($actuator['command_type'] ?? 'toggle') === 'duration')
                        <!-- Duration-based actuator (e.g., spray pump, fill valve) -->
                        <form class="actuator-control-form space-y-3" 
                              data-actuator-id="{{ $actuator['id'] }}"
                              data-device-id="{{ $device->id }}">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                                    {{ $actuator['duration_label'] ?? 'Duration' }}
                                </label>
                                <div class="flex gap-2">
                                    <input 
                                        type="number" 
                                        name="duration"
                                        min="{{ $actuator['min_duration'] ?? 100 }}"
                                        max="{{ $actuator['max_duration'] ?? 30000 }}"
                                        value="{{ $actuator['default_duration'] ?? 1000 }}"
                                        class="flex-1 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 px-3 py-2 text-sm"
                                    />
                                    <span class="flex items-center text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $actuator['duration_unit'] ?? 'ms' }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @php
                                        $minD = $actuator['min_duration'] ?? 100;
                                        $maxD = $actuator['max_duration'] ?? 30000;
                                        $presets = [250, 500, 1000, 2000, 5000];
                                    @endphp
                                    @foreach($presets as $preset)
                                        @if($preset >= $minD && $preset <= $maxD)
                                            <button type="button" class="duration-preset px-2 py-1 text-xs rounded bg-neutral-200 dark:bg-neutral-600 text-neutral-800 dark:text-neutral-200" data-value="{{ $preset }}">{{ $preset }} ms</button>
                                        @endif
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                    Range: {{ $minD }}–{{ $maxD }} ms
                                </p>
                                @if(isset($actuator['duration_help']))
                                    <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $actuator['duration_help'] }}
                                    </p>
                                @endif
                            </div>
                            
                            @if(isset($actuator['amount_unit']))
                                <div>
                                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                                        {{ $actuator['amount_label'] ?? 'Amount' }}
                                    </label>
                                    <div class="flex gap-2">
                                        <input 
                                            type="number" 
                                            name="amount"
                                            min="{{ $actuator['min_amount'] ?? 0.1 }}"
                                            max="{{ $actuator['max_amount'] ?? 10 }}"
                                            step="0.1"
                                            value="{{ $actuator['default_amount'] ?? 1 }}"
                                            class="flex-1 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 px-3 py-2 text-sm"
                                        />
                                        <span class="flex items-center text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $actuator['amount_unit'] }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                            
                            <button 
                                type="submit"
                                class="actuator-submit-btn w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50"
                            >
                                Activate
                            </button>
                            
                            <div class="actuator-status-message text-xs text-center" style="min-height: 1.25rem;"></div>
                        </form>
                        
                    @else
                        <!-- Toggle-based actuator (e.g., light, fan) -->
                        <form class="actuator-control-form space-y-3" 
                              data-actuator-id="{{ $actuator['id'] }}"
                              data-device-id="{{ $device->id }}">
                            @csrf
                            
                            <div class="flex gap-2">
                                <button 
                                    type="submit"
                                    name="action"
                                    value="on"
                                    class="actuator-submit-btn flex-1 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500/20 disabled:opacity-50"
                                >
                                    Turn On
                                </button>
                                <button 
                                    type="submit"
                                    name="action"
                                    value="off"
                                    class="actuator-submit-btn flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 disabled:opacity-50"
                                >
                                    Turn Off
                                </button>
                            </div>
                            
                            <div class="actuator-status-message text-xs text-center" style="min-height: 1.25rem;"></div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
        
        @if(empty($actuators))
            <div class="text-center py-12">
                <p class="text-neutral-500 dark:text-neutral-400">No actuators configured for this device</p>
            </div>
        @endif
    </div>
</div>

<script>
    const actuatorForms = document.querySelectorAll('.actuator-control-form');
    const activeCommands = new Map(); // Track active commands per actuator

    // Listen for global command status events
    window.addEventListener('command-status', (e) => {
        updateActuatorStatus(e.detail);
    });

    // Setup form handlers
    actuatorForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const deviceId = window.deviceId || {{ $device->id }};
            const actuatorId = form.dataset.actuatorId;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('.actuator-submit-btn');
            const statusMsg = form.querySelector('.actuator-status-message');
            const statusDot = document.getElementById(`actuator-status-${actuatorId}`);
            
            // Build command params
            const params = {};
            const action = formData.get('action');
            
            if (action) {
                params.action = action;
            } else {
                if (formData.get('duration')) {
                    params.duration_ms = parseInt(formData.get('duration'));
                }
                if (formData.get('amount')) {
                    params.amount = parseFloat(formData.get('amount'));
                }
            }
            
            try {
                // Disable buttons
                form.querySelectorAll('button').forEach(btn => btn.disabled = true);
                statusMsg.textContent = '⏳ Sending command...';
                statusMsg.className = 'actuator-status-message text-xs text-center text-blue-600 dark:text-blue-400';
                
                if (statusDot) {
                    statusDot.className = 'h-2 w-2 rounded-full bg-blue-500 animate-pulse';
                }
                
                const response = await fetch(`/api/growdash/devices/${window.devicePublicId}/commands`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        type: actuatorId,
                        params: params
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    // Track command id for status updates; handle multiple shapes
                    const cmdId = data.command_id || data.command?.id || data.id;
                    if (cmdId) activeCommands.set(actuatorId, cmdId);
                    
                    statusMsg.textContent = '⏳ Executing...';
                    statusMsg.className = 'actuator-status-message text-xs text-center text-yellow-600 dark:text-yellow-400';
                    
                    if (statusDot) {
                        statusDot.className = 'h-2 w-2 rounded-full bg-yellow-500 animate-pulse';
                    }
                } else {
                    const error = await response.json();
                    statusMsg.textContent = `✗ ${error.message || 'Failed'}`;
                    statusMsg.className = 'actuator-status-message text-xs text-center text-red-600 dark:text-red-400';
                    
                    if (statusDot) {
                        statusDot.className = 'h-2 w-2 rounded-full bg-red-500';
                    }
                    
                    // Re-enable after error
                    setTimeout(() => {
                        form.querySelectorAll('button').forEach(btn => btn.disabled = false);
                        statusMsg.textContent = '';
                        if (statusDot) {
                            statusDot.className = 'h-2 w-2 rounded-full bg-neutral-400';
                        }
                    }, 3000);
                }
            } catch (error) {
                statusMsg.textContent = `✗ ${error.message}`;
                statusMsg.className = 'actuator-status-message text-xs text-center text-red-600 dark:text-red-400';
                
                if (statusDot) {
                    statusDot.className = 'h-2 w-2 rounded-full bg-red-500';
                }
                
                setTimeout(() => {
                    form.querySelectorAll('button').forEach(btn => btn.disabled = false);
                    statusMsg.textContent = '';
                    if (statusDot) {
                        statusDot.className = 'h-2 w-2 rounded-full bg-neutral-400';
                    }
                }, 3000);
            }
        });
    });

    // Update actuator status from WebSocket
    function updateActuatorStatus(event) {
        // Find which actuator this command belongs to
        let actuatorId = null;
        const eventCommandId = event.command_id || event.command?.id || event.id;
        const eventStatus = event.status || event.command?.status || event.result_status;
        const eventMessage = event.result_message || event.command?.result_message;
        activeCommands.forEach((commandId, actId) => {
            if (eventCommandId && commandId === eventCommandId) {
                actuatorId = actId;
            }
        });
        
        if (!actuatorId) return;
        
        const form = document.querySelector(`[data-actuator-id="${actuatorId}"]`);
        if (!form) return;
        
        const statusMsg = form.querySelector('.actuator-status-message');
        const statusDot = document.getElementById(`actuator-status-${actuatorId}`);
        
        if (eventStatus === 'success' || eventStatus === 'completed') {
            statusMsg.textContent = '✓ Success';
            statusMsg.className = 'actuator-status-message text-xs text-center text-green-600 dark:text-green-400';
            
            if (statusDot) {
                statusDot.className = 'h-2 w-2 rounded-full bg-green-500';
            }
            
            // Re-enable and clear after success
            setTimeout(() => {
                form.querySelectorAll('button').forEach(btn => btn.disabled = false);
                statusMsg.textContent = '';
                if (statusDot) {
                    statusDot.className = 'h-2 w-2 rounded-full bg-neutral-400';
                }
                activeCommands.delete(actuatorId);
            }, 2000);
            
        } else if (eventStatus === 'failed') {
            statusMsg.textContent = `✗ ${eventMessage || 'Failed'}`;
            statusMsg.className = 'actuator-status-message text-xs text-center text-red-600 dark:text-red-400';
            
            if (statusDot) {
                statusDot.className = 'h-2 w-2 rounded-full bg-red-500';
            }
            
            // Re-enable after failure
            setTimeout(() => {
                form.querySelectorAll('button').forEach(btn => btn.disabled = false);
                statusMsg.textContent = '';
                if (statusDot) {
                    statusDot.className = 'h-2 w-2 rounded-full bg-neutral-400';
                }
                activeCommands.delete(actuatorId);
            }, 3000);
        }
    }

    // Preset buttons: set duration quickly
    document.querySelectorAll('.duration-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = btn.closest('form');
            const input = form?.querySelector('input[name="duration"]');
            if (input) input.value = btn.dataset.value;
        });
    });
</script>
