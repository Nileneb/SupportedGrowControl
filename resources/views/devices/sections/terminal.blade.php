<div class="space-y-4">
    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Serial Console</h3>
        
        <div class="space-y-4">
            <!-- WebSocket Status -->
            <div class="flex items-center justify-between p-3 rounded-lg bg-neutral-50 dark:bg-neutral-700/50">
                <span class="text-sm text-neutral-700 dark:text-neutral-300">WebSocket Status:</span>
                <span id="ws-status" class="text-sm font-medium">⏳ Connecting...</span>
            </div>

            <!-- Serial Output -->
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                    Serial Output
                </label>
                <div id="serial-console" 
                     class="h-96 overflow-y-auto rounded-lg border border-neutral-200 dark:border-neutral-600 bg-neutral-900 p-4 font-mono text-xs text-green-400"
                     style="scroll-behavior: smooth;">
                    <div class="text-neutral-500">Waiting for device output...</div>
                </div>
            </div>

            <!-- Command Input -->
            <form id="serial-command-form" class="space-y-2">
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    Send Command
                </label>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        id="serial-command-input"
                        placeholder="Enter command (e.g., Status, TDS, Spray 1000)"
                        class="flex-1 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                    <button 
                        type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:opacity-50"
                    >
                        Send
                    </button>
                </div>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Common commands: Status, TDS, Spray &lt;ms&gt;, FillL &lt;liters&gt;, SprayOn, SprayOff
                </p>
            </form>

            <!-- Command History -->
            <div>
                <h4 class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">Recent Commands</h4>
                <div id="command-history" class="space-y-1 max-h-40 overflow-y-auto">
                    <div class="text-xs text-neutral-500 dark:text-neutral-400">No commands sent yet</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let serialLogCount = 0;
    const MAX_SERIAL_LOGS = 500;
    const deviceId = {{ $device->id }};
    let commandHistory = [];
    const MAX_HISTORY = 20;

    // WebSocket status updates
    window.addEventListener('ws-connected', () => {
        document.getElementById('ws-status').innerHTML = '<span class="text-green-600 dark:text-green-400">✓ Connected</span>';
    });

    window.addEventListener('ws-disconnected', () => {
        document.getElementById('ws-status').innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Disconnected</span>';
    });

    window.addEventListener('ws-error', (event) => {
        document.getElementById('ws-status').innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Error</span>';
    });

    // Subscribe to device channel
    if (window.Echo) {
        window.Echo.private(`device.${deviceId}`)
            .listen('DeviceTelemetryReceived', (event) => {
                if (event.telemetry && event.telemetry.serial_output) {
                    addSerialLog(event.telemetry.serial_output, 'device');
                }
            })
            .listen('CommandStatusUpdated', (event) => {
                if (event.type === 'serial_command') {
                    updateCommandHistory(event);
                }
            });
    }

    // Add log to serial console
    function addSerialLog(message, source = 'device') {
        const console = document.getElementById('serial-console');
        const timestamp = new Date().toLocaleTimeString();
        const color = source === 'user' ? 'text-blue-400' : 'text-green-400';
        const prefix = source === 'user' ? '→' : '←';
        
        // Remove "Waiting..." message if present
        if (serialLogCount === 0) {
            console.innerHTML = '';
        }
        
        const logEntry = document.createElement('div');
        logEntry.className = `${color} mb-1`;
        logEntry.innerHTML = `<span class="text-neutral-500">[${timestamp}]</span> ${prefix} ${escapeHtml(message)}`;
        
        console.appendChild(logEntry);
        serialLogCount++;
        
        // Limit logs
        if (serialLogCount > MAX_SERIAL_LOGS) {
            console.removeChild(console.firstChild);
            serialLogCount--;
        }
        
        // Auto-scroll
        console.scrollTop = console.scrollHeight;
    }

    // Send serial command
    document.getElementById('serial-command-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const input = document.getElementById('serial-command-input');
        const command = input.value.trim();
        
        if (!command) return;
        
        try {
            const response = await fetch(`/api/devices/${deviceId}/commands`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    type: 'serial_command',
                    params: {
                        command: command
                    }
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                addSerialLog(command, 'user');
                input.value = '';
                
                // Add to history
                commandHistory.unshift({
                    id: data.command_id,
                    command: command,
                    status: 'pending',
                    timestamp: new Date()
                });
                
                if (commandHistory.length > MAX_HISTORY) {
                    commandHistory.pop();
                }
                
                renderCommandHistory();
            } else {
                const error = await response.json();
                addSerialLog(`Error: ${error.message || 'Failed to send command'}`, 'user');
            }
        } catch (error) {
            addSerialLog(`Error: ${error.message}`, 'user');
        }
    });

    // Update command history from WebSocket
    function updateCommandHistory(event) {
        const cmd = commandHistory.find(c => c.id === event.command_id);
        if (cmd) {
            cmd.status = event.status;
            cmd.result = event.result_message;
            renderCommandHistory();
            
            // Also log result if available
            if (event.result_message) {
                addSerialLog(event.result_message, 'device');
            }
        }
    }

    // Render command history
    function renderCommandHistory() {
        const container = document.getElementById('command-history');
        
        if (commandHistory.length === 0) {
            container.innerHTML = '<div class="text-xs text-neutral-500 dark:text-neutral-400">No commands sent yet</div>';
            return;
        }
        
        container.innerHTML = commandHistory.map(cmd => {
            const statusColors = {
                pending: 'text-blue-600 dark:text-blue-400',
                executing: 'text-yellow-600 dark:text-yellow-400',
                success: 'text-green-600 dark:text-green-400',
                failed: 'text-red-600 dark:text-red-400'
            };
            
            const statusIcons = {
                pending: '⏳',
                executing: '⚙️',
                success: '✓',
                failed: '✗'
            };
            
            return `
                <div class="flex items-center gap-2 text-xs p-2 rounded bg-neutral-50 dark:bg-neutral-700/30">
                    <span class="${statusColors[cmd.status]}">${statusIcons[cmd.status]}</span>
                    <span class="flex-1 font-mono text-neutral-900 dark:text-neutral-100">${escapeHtml(cmd.command)}</span>
                    <span class="text-neutral-500 dark:text-neutral-400">${cmd.timestamp.toLocaleTimeString()}</span>
                </div>
            `;
        }).join('');
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
