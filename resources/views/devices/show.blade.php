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
                            <div class="text-neutral-500"># Serial console will appear here when device sends data...</div>
                            <div class="text-neutral-500"># Waiting for output...</div>
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

            <!-- Device Logs -->
            <div class="flex flex-col rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden">
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

    @if($device->status === 'online')
    <script>
        // Placeholder for WebSocket/Polling implementation
        const deviceId = '{{ $device->public_id }}';
        
        // Serial command form
        document.getElementById('serial-command-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('serial-command');
            const command = input.value.trim();
            
            if (!command) return;
            
            // TODO: Send command to backend API
            console.log('Sending command:', command);
            
            // Add to output
            const output = document.getElementById('serial-output');
            const line = document.createElement('div');
            line.className = 'text-yellow-400';
            line.textContent = `> ${command}`;
            output.appendChild(line);
            
            input.value = '';
            output.scrollTop = output.scrollHeight;
        });
    </script>
    @endif
</x-layouts.app>
