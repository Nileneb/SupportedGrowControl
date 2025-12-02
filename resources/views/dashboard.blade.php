<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <!-- Total Devices -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Devices</h3>
                    <p class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">{{ $totalDevices }}</p>
                </div>
            </div>

            <!-- Online Devices -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Online</h3>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $onlineDevices }}</p>
                </div>
            </div>

            <!-- Paired Devices -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Paired</h3>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $pairedDevices }}</p>
                </div>
            </div>
        </div>

        <!-- Devices List -->
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="p-6">
                <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100 mb-4">Your Devices</h2>
                
                @if($devices->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-neutral-500 dark:text-neutral-400">No devices registered yet.</p>
                        <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-2">Start by registering a GrowDash agent.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($devices as $device)
                            <a href="{{ route('devices.show', $device->public_id) }}" class="block">
                                <div class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50 transition-colors">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $device->name }}</h3>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $device->bootstrap_id }}</p>
                                        @if($device->device_info)
                                            <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">
                                                {{ $device->device_info['platform'] ?? 'Unknown' }} · 
                                                {{ $device->device_info['hostname'] ?? 'Unknown' }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full 
                                            @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                            @elseif($device->status === 'paired') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                            @elseif($device->status === 'offline') bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                                            @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                            @endif">
                                            {{ ucfirst($device->status) }}
                                        </span>
                                        @if($device->last_seen_at)
                                            <span class="text-xs text-neutral-400 dark:text-neutral-500">
                                                {{ $device->last_seen_at->diffForHumans() }}
                                            </span>
                                        @endif
                                        <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
















































































































































































</x-layouts.app>    @endif    </script>        });            output.scrollTop = output.scrollHeight;            input.value = '';                        output.appendChild(line);            line.textContent = `> ${command}`;            line.className = 'text-yellow-400';            const line = document.createElement('div');            const output = document.getElementById('serial-output');            // Add to output                        console.log('Sending command:', command);            // TODO: Send command to backend API                        if (!command) return;                        const command = input.value.trim();            const input = document.getElementById('serial-command');            e.preventDefault();        document.getElementById('serial-command-form')?.addEventListener('submit', async (e) => {        // Serial command form                const deviceId = '{{ $device->public_id }}';        // Placeholder for WebSocket/Polling implementation    <script>    @if($device->status === 'online')    </div>        </div>            </div>                </div>                    @endforelse                        </div>                            No logs available yet                        <div class="text-center py-8 text-neutral-500 dark:text-neutral-400">                    @empty                        </div>                            </span>                                {{ $log->message }}                            <span class="text-neutral-700 dark:text-neutral-300 break-all">                            </span>                                {{ strtoupper($log->log_level ?? 'debug') }}                                @endif">                                @else bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400                                @elseif($log->log_level === 'info') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400                                @elseif($log->log_level === 'warn') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400                                @if($log->log_level === 'error') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400                            <span class="px-2 py-0.5 text-xs font-medium rounded shrink-0                            </span>                                {{ $log->created_at->format('H:i:s') }}                            <span class="text-xs text-neutral-400 dark:text-neutral-500 font-mono shrink-0">                        <div class="flex gap-3 text-sm">                    @forelse($logs as $log)                <div class="flex-1 p-4 overflow-auto space-y-2">                                </div>                    </button>                        Refresh                    >                        class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"                        onclick="location.reload()"                     <button                     <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">Device Logs</h2>                <div class="flex items-center justify-between p-4 border-b border-neutral-200 dark:border-neutral-700">            <div class="flex flex-col rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden">            <!-- Device Logs -->            </div>                @endif                </div>                    </form>                        </button>                            Send                        >                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"                            type="submit"                         <button                         />                            class="flex-1 px-3 py-2 bg-neutral-800 border border-neutral-600 rounded text-white placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-blue-500"                            placeholder="Enter serial command..."                             id="serial-command"                             type="text"                         <input                     <form id="serial-command-form" class="flex gap-2">                <div class="p-3 border-t border-neutral-700">                @if($device->status === 'online')                                </div>                    @endif                        <div class="text-neutral-500">Device is offline. Serial console unavailable.</div>                    @else                        </div>                            <div class="text-neutral-500"># Waiting for output...</div>                            <div class="text-neutral-500"># Serial console will appear here when device sends data...</div>                        <div id="serial-output" class="space-y-1">                    @if($device->status === 'online')                <div class="flex-1 p-4 overflow-auto bg-neutral-900 text-green-400 font-mono text-sm">                                </div>                    @endif                        <span class="text-xs text-neutral-500 dark:text-neutral-400">Offline</span>                    @else                        </div>                            <span class="text-xs text-green-600 dark:text-green-400">Live</span>                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>                        <div class="flex items-center gap-2">                    @if($device->status === 'online')                    <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">Serial Console</h2>                <div class="flex items-center justify-between p-4 border-b border-neutral-200 dark:border-neutral-700">            <div class="flex flex-col rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 overflow-hidden">            <!-- Serial Console -->        <div class="grid grid-cols-2 gap-4 flex-1 min-h-0">        @endif        </div>            </div>                </div>                    {{ $device->paired_at?->diffForHumans() ?? 'Never' }}                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">                <div class="text-xs text-neutral-500 dark:text-neutral-400">Paired</div>            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">            </div>                </div>                    {{ $device->device_info['version'] ?? 'Unknown' }}                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">                <div class="text-xs text-neutral-500 dark:text-neutral-400">Version</div>            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">            </div>                </div>                    {{ $device->device_info['hostname'] ?? 'Unknown' }}                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">                <div class="text-xs text-neutral-500 dark:text-neutral-400">Hostname</div>            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">            </div>                </div>                    {{ $device->device_info['platform'] ?? 'Unknown' }}                <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">                <div class="text-xs text-neutral-500 dark:text-neutral-400">Platform</div>            <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">        <div class="grid grid-cols-4 gap-4">        @if($device->device_info)        <!-- Device Info -->        </div>            </div>                @endif                    </span>                        Last seen: {{ $device->last_seen_at->diffForHumans() }}                    <span class="text-sm text-neutral-500 dark:text-neutral-400">                @if($device->last_seen_at)                </span>                    {{ ucfirst($device->status) }}                    @endif">                    @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400                    @elseif($device->status === 'offline') bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400                    @elseif($device->status === 'paired') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400                    @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400                <span class="px-4 py-2 text-sm font-medium rounded-full             <div class="flex items-center gap-3">                        </div>                </div>                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $device->bootstrap_id }}</p>                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">{{ $device->name }}</h1>                <div>                </a>                    </svg>                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">                <a href="{{ route('dashboard') }}" class="text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">            <div class="flex items-center gap-4">        <div class="flex items-center justify-between">        <!-- Device Header -->    <div class="flex h-full w-full flex-1 flex-col gap-4"><x-layouts.app :title="$device->name">@endphp        ->get();        ->limit(100)        ->orderBy('created_at', 'desc')    $logs = $device->arduinoLogs()            ->firstOrFail();        ->where('user_id', auth()->id())    $device = Device::where('public_id', $device)            @php
            $devices = auth()->user()->devices;
            $totalDevices = $devices->count();
            $onlineDevices = $devices->where('status', 'online')->count();
            $pairedDevices = $devices->where('status', 'paired')->count();
        @endphp

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <!-- Total Devices -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Devices</h3>
                    <p class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">{{ $totalDevices }}</p>
                </div>
            </div>

            <!-- Online Devices -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Online</h3>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $onlineDevices }}</p>
                </div>
            </div>

            <!-- Paired Devices -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                <div class="flex flex-col gap-2">
                    <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Paired</h3>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $pairedDevices }}</p>
                </div>
            </div>
        </div>

        <!-- Devices List -->
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
            <div class="p-6">
                <h2 class="text-xl font-bold text-neutral-900 dark:text-neutral-100 mb-4">Your Devices</h2>
                
                @if($devices->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-neutral-500 dark:text-neutral-400">No devices registered yet.</p>
                        <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-2">Start by registering a GrowDash agent.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($devices as $device)
                            <a href="{{ route('devices.show', $device->public_id) }}" class="block">
                                <div class="flex items-center justify-between p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50 transition-colors">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $device->name }}</h3>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $device->bootstrap_id }}</p>
                                        @if($device->device_info)
                                            <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">
                                                {{ $device->device_info['platform'] ?? 'Unknown' }} · 
                                                {{ $device->device_info['hostname'] ?? 'Unknown' }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full 
                                            @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                            @elseif($device->status === 'paired') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                            @elseif($device->status === 'offline') bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                                            @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                            @endif">
                                            {{ ucfirst($device->status) }}
                                        </span>
                                        @if($device->last_seen_at)
                                            <span class="text-xs text-neutral-400 dark:text-neutral-500">
                                                {{ $device->last_seen_at->diffForHumans() }}
                                            </span>
                                        @endif
                                        <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
