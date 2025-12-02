<x-layouts.app :title="$device->name">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <!-- Device Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{{ $device->name }}</h1>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $device->bootstrap_id }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 text-sm font-medium rounded-full 
                    @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                    @elseif($device->status === 'paired') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                    @elseif($device->status === 'offline') bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                    @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                    @endif">
                    {{ ucfirst($device->status) }}
                </span>
                @if($device->last_seen_at)
                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                        Last seen: {{ $device->last_seen_at->diffForHumans() }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Device Info -->
        @if($device->device_info)
        <div class="grid grid-cols-4 gap-4">
            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
                <div class="text-xs text-neutral-500 dark:text-neutral-400">Platform</div>
                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    {{ $device->device_info['platform'] ?? 'Unknown' }}
                </div>
            </div>
            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
                <div class="text-xs text-neutral-500 dark:text-neutral-400">Hostname</div>
                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    {{ $device->device_info['hostname'] ?? 'Unknown' }}
                </div>
            </div>
            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
                <div class="text-xs text-neutral-500 dark:text-neutral-400">Version</div>
                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    {{ $device->device_info['version'] ?? 'Unknown' }}
                </div>
            </div>
            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
                <div class="text-xs text-neutral-500 dark:text-neutral-400">Paired</div>
                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                    {{ $device->paired_at?->diffForHumans() ?? 'Never' }}
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4 flex-1 min-h-0">
            <!-- Serial Console -->
            <div class="flex flex-col rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-neutral-200 dark:border-neutral-700">
                    <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">Serial Console</h2>
                    @if($device->status === 'online')
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-xs text-green-600 dark:text-green-400">Live</span>
                        </div>
                    @else
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">Offline</span>
                    @endif
                </div>
                
                <div class="flex-1 p-4 overflow-auto bg-neutral-900 text-green-400 font-mono text-sm">
                    @if($device->status === 'online')
                        <div id="serial-output" class="space-y-1">
                            <!-- Output will be added here by JavaScript -->
                        </div>
                    @else
                        <div class="text-neutral-500">Device is offline. Serial console unavailable.</div>
                    @endif
                </div>
                
                @if($device->status === 'online')
                <div class="p-3 border-t border-neutral-700">
                    <form id="serial-command-form" class="flex gap-2">
                        <input 
                            type="text" 
                            id="serial-command" 
                            placeholder="Enter serial command..." 
                            class="flex-1 px-3 py-2 bg-neutral-800 border border-neutral-600 rounded text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            Send
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <!-- Right Column: Logs & Commands -->
            <div class="flex flex-col gap-4">
                <!-- Command History -->
                <div class="flex flex-col rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden max-h-[300px]">
                    <div class="flex items-center justify-between p-4 border-b border-neutral-200 dark:border-neutral-700">
                        <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">Command History</h2>
                        <button 
                            onclick="refreshCommandHistory()" 
                            class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
                        >
                            Refresh
                        </button>
                    </div>
                    
                    <div id="command-history" class="flex-1 p-4 overflow-auto space-y-2">
                        <div class="text-center py-4 text-neutral-500 dark:text-neutral-400 text-sm">
                            No commands sent yet
                        </div>
                    </div>
                </div>

                <!-- Device Logs -->
                <div class="flex flex-col rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden flex-1">
                    <div class="flex items-center justify-between p-4 border-b border-neutral-200 dark:border-neutral-700">
                        <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">Device Logs</h2>
                        <button 
                            onclick="location.reload()" 
                            class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
                        >
                            Refresh
                        </button>
                    </div>
                    
                    <div class="flex-1 p-4 overflow-auto space-y-2">
                        @forelse($logs as $log)
                            <div class="flex gap-3 text-sm">
                                <span class="text-xs text-neutral-400 dark:text-neutral-500 font-mono shrink-0">
                                    {{ $log->created_at->format('H:i:s') }}
                                </span>
                                <span class="px-2 py-0.5 text-xs font-medium rounded shrink-0
                                    @if($log->log_level === 'error') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($log->log_level === 'warn') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @elseif($log->log_level === 'info') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                                    @endif">
                                    {{ strtoupper($log->log_level ?? 'debug') }}
                                </span>
                                <span class="text-neutral-700 dark:text-neutral-300 break-all">
                                    {{ $log->message }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">
                                No logs available yet
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($device->status === 'online')
    <script>
        const deviceId = '{{ $device->public_id }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        // Command history
        let commandHistory = [];
        let pollingInterval = null;
        
        // Send serial command to device
        document.getElementById('serial-command-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('serial-command');
            const command = input.value.trim();
            
            if (!command) return;
            
            // Add command to output (sent)
            addToOutput(`> ${command}`, 'text-yellow-400');
            input.value = '';
            
            try {
                const response = await fetch(`/api/growdash/devices/${deviceId}/commands`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        type: 'serial_command',
                        params: { command: command }
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addToOutput(`✓ Command queued (ID: ${data.command.id})`, 'text-green-500');
                    // Poll for result
                    pollCommandResult(data.command.id);
                } else {
                    addToOutput(`✗ Failed: ${data.message}`, 'text-red-500');
                }
            } catch (error) {
                console.error('Error sending command:', error);
                addToOutput(`✗ Error: ${error.message}`, 'text-red-500');
            }
        });
        
        // Add line to serial output
        function addToOutput(text, className = 'text-green-400') {
            const output = document.getElementById('serial-output');
            const line = document.createElement('div');
            line.className = className;
            line.textContent = text;
            output.appendChild(line);
            output.scrollTop = output.scrollHeight;
            
            // Limit output to 100 lines
            while (output.children.length > 100) {
                output.removeChild(output.firstChild);
            }
        }
        
        // Poll for command result
        function pollCommandResult(commandId, attempts = 0, maxAttempts = 30) {
            if (attempts >= maxAttempts) {
                addToOutput(`⚠ Command ${commandId} timeout`, 'text-yellow-500');
                return;
            }
            
            setTimeout(async () => {
                try {
                    const response = await fetch(`/api/growdash/devices/${deviceId}/commands?limit=1`, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.commands.length > 0) {
                        const command = data.commands.find(c => c.id === commandId);
                        
                        if (command) {
                            if (command.status === 'completed') {
                                addToOutput(`← ${command.result_message || 'OK'}`, 'text-green-400');
                                return;
                            } else if (command.status === 'failed') {
                                addToOutput(`✗ ${command.result_message || 'Failed'}`, 'text-red-500');
                                return;
                            } else if (command.status === 'executing') {
                                addToOutput(`⏳ Executing...`, 'text-blue-400');
                            }
                        }
                    }
                    
                    // Continue polling
                    pollCommandResult(commandId, attempts + 1, maxAttempts);
                    
                } catch (error) {
                    console.error('Error polling command:', error);
                    pollCommandResult(commandId, attempts + 1, maxAttempts);
                }
            }, 2000); // Poll every 2 seconds
        }
        
        // Auto-refresh command history every 10 seconds
        function startCommandHistoryPolling() {
            pollingInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/growdash/devices/${deviceId}/commands?limit=10`, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        updateCommandHistory(data.commands);
                    }
                } catch (error) {
                    console.error('Error fetching command history:', error);
                }
            }, 10000);
        }
        
        function updateCommandHistory(commands) {
            const container = document.getElementById('command-history');
            
            if (!commands || commands.length === 0) {
                container.innerHTML = '<div class="text-center py-4 text-neutral-500 dark:text-neutral-400 text-sm">No commands sent yet</div>';
                return;
            }
            
            container.innerHTML = '';
            
            commands.forEach(cmd => {
                const cmdEl = document.createElement('div');
                cmdEl.className = 'p-3 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900';
                
                const statusColors = {
                    'pending': 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
                    'executing': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'completed': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'failed': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                };
                
                cmdEl.innerHTML = `
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex-1">
                            <div class="font-mono text-sm text-neutral-900 dark:text-neutral-100">
                                ${cmd.params?.command || cmd.type}
                            </div>
                            <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                ${new Date(cmd.created_at).toLocaleTimeString()}
                                ${cmd.created_by ? `by ${cmd.created_by}` : ''}
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-xs font-medium rounded shrink-0 ${statusColors[cmd.status] || statusColors['pending']}">
                            ${cmd.status.toUpperCase()}
                        </span>
                    </div>
                    ${cmd.result_message ? `<div class="text-xs text-neutral-600 dark:text-neutral-400 mt-1">${cmd.result_message}</div>` : ''}
                `;
                
                container.appendChild(cmdEl);
            });
            
            // Check for new completed commands (for terminal output)
            commands.forEach(cmd => {
                if (cmd.status === 'completed' && !commandHistory.includes(cmd.id)) {
                    commandHistory.push(cmd.id);
                }
            });
            
            // Keep only last 50 command IDs in history
            if (commandHistory.length > 50) {
                commandHistory = commandHistory.slice(-50);
            }
        }
        
        // Refresh command history manually
        async function refreshCommandHistory() {
            try {
                const response = await fetch(`/api/growdash/devices/${deviceId}/commands?limit=20`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                
                const data = await response.json();
                
                if (data.success) {
                    updateCommandHistory(data.commands);
                }
            } catch (error) {
                console.error('Error fetching command history:', error);
            }
        }
        
        // Start polling when page loads
        startCommandHistoryPolling();
        refreshCommandHistory(); // Initial load
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        });
        
        // Initial message
        addToOutput('# Serial console ready. Type commands and press Enter.', 'text-neutral-500');
    </script>
    @endif
</x-layouts.app>
