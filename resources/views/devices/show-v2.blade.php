<x-layouts.app :title="$device->name">
    @push('scripts')
    <script>
        // Global device context
        window.deviceId = {{ $device->id }};
        window.devicePublicId = '{{ $device->public_id }}';
    </script>
    @endpush
    
    <div class="flex h-full w-full flex-1 gap-4">
        <!-- Sidebar Navigation -->
        <div class="w-64 flex-shrink-0 space-y-2">
            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4">
                <h2 class="font-semibold text-neutral-900 dark:text-neutral-100 mb-3">{{ $device->name }}</h2>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-4">{{ $device->bootstrap_id }}</p>
                
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-3 py-1 text-xs font-medium rounded-full
                        @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                        @else bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                        @endif">
                        {{ ucfirst($device->status) }}
                    </span>
                </div>

                @if($device->last_seen_at)
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-4">
                        Last seen: {{ $device->last_seen_at->diffForHumans() }}
                    </p>
                @endif

                <nav class="space-y-1">
                    <!-- Terminal (always visible) -->
                    <button 
                        onclick="showSection('terminal')"
                        data-section="terminal"
                        class="nav-item w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors
                            text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700"
                    >
                        <span class="mr-2">💻</span> Terminal
                    </button>

                    @if(!empty($sensors))
                    <!-- Sensors (only if device has sensors) -->
                    <button 
                        onclick="showSection('sensors')"
                        data-section="sensors"
                        class="nav-item w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors
                            text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700"
                    >
                        <span class="mr-2">📊</span> Sensors <span class="text-xs opacity-60">({{ count($sensors) }})</span>
                    </button>
                    @endif

                    @if(!empty($actuators))
                    <!-- Actuators (only if device has actuators) -->
                    <button 
                        onclick="showSection('actuators')"
                        data-section="actuators"
                        class="nav-item w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors
                            text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700"
                    >
                        <span class="mr-2">⚙️</span> Actuators <span class="text-xs opacity-60">({{ count($actuators) }})</span>
                    </button>
                    @endif

                    <!-- Device Info (always visible) -->
                    <button 
                        onclick="showSection('info')"
                        data-section="info"
                        class="nav-item w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors
                            text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-700"
                    >
                        <span class="mr-2">ℹ️</span> Device Info
                    </button>
                </nav>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 min-w-0">
            <!-- Terminal Section (always available) -->
            <div id="section-terminal" class="content-section hidden">
                @include('devices.sections.terminal', ['device' => $device])
            </div>

            <!-- Sensors Section (conditional) -->
            @if(!empty($sensors))
            <div id="section-sensors" class="content-section hidden">
                @include('devices.sections.sensors', ['device' => $device, 'sensors' => $sensors, 'sensorReadings' => $sensorReadings])
            </div>
            @endif

            <!-- Actuators Section (conditional) -->
            @if(!empty($actuators))
            <div id="section-actuators" class="content-section hidden">
                @include('devices.sections.actuators', ['device' => $device, 'actuators' => $actuators])
            </div>
            @endif

            <!-- Device Info Section -->
            <div id="section-info" class="content-section hidden">
                @include('devices.sections.info', ['device' => $device])
            </div>
        </div>
    </div>

    <script>
        // Section navigation
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Remove active state from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-700', 'dark:text-blue-400');
                item.classList.add('text-neutral-700', 'dark:text-neutral-300');
            });
            
            // Show selected section
            const section = document.getElementById(`section-${sectionName}`);
            if (section) {
                section.classList.remove('hidden');
            }
            
            // Highlight active nav item
            const navItem = document.querySelector(`[data-section="${sectionName}"]`);
            if (navItem) {
                navItem.classList.remove('text-neutral-700', 'dark:text-neutral-300');
                navItem.classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'text-blue-700', 'dark:text-blue-400');
            }
            
            // Store preference
            localStorage.setItem('device-view-section', sectionName);
        }
        
        // WebSocket initialization and global event handlers
        document.addEventListener('DOMContentLoaded', () => {
            const deviceId = {{ $device->id }};
            
            // Initialize WebSocket status tracking
            let wsConnected = false;
            let wsStatusTimeout = null;
            
            // Set initial connecting status
            const wsStatusEl = document.getElementById('ws-status');
            if (wsStatusEl) {
                wsStatusEl.innerHTML = '<span class="text-yellow-600 dark:text-yellow-400">⏳ Connecting...</span>';
            }
            
            // Timeout fallback
            wsStatusTimeout = setTimeout(() => {
                if (!wsConnected && wsStatusEl) {
                    wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Connection timeout</span>';
                }
            }, 5000);
            
            // Subscribe to device channel
            if (window.Echo) {
                try {
                    window.Echo.private(`device.${deviceId}`)
                        .listen('DeviceTelemetryReceived', (event) => {
                            window.dispatchEvent(new CustomEvent('device-telemetry', { detail: event }));
                        })
                        .listen('CommandStatusUpdated', (event) => {
                            window.dispatchEvent(new CustomEvent('command-status', { detail: event }));
                        })
                        .listen('DeviceCapabilitiesUpdated', (event) => {
                            window.dispatchEvent(new CustomEvent('device-capabilities', { detail: event }));
                        });
                    
                    // Listen for connection events
                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        window.Echo.connector.pusher.connection.bind('connected', () => {
                            wsConnected = true;
                            if (wsStatusTimeout) clearTimeout(wsStatusTimeout);
                            if (wsStatusEl) {
                                wsStatusEl.innerHTML = '<span class="text-green-600 dark:text-green-400">✓ Connected</span>';
                            }
                            window.dispatchEvent(new CustomEvent('ws-connected'));
                        });
                        
                        window.Echo.connector.pusher.connection.bind('disconnected', () => {
                            wsConnected = false;
                            if (wsStatusEl) {
                                wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Disconnected</span>';
                            }
                            window.dispatchEvent(new CustomEvent('ws-disconnected'));
                        });
                        
                        window.Echo.connector.pusher.connection.bind('error', (error) => {
                            if (wsStatusEl) {
                                wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Error</span>';
                            }
                            window.dispatchEvent(new CustomEvent('ws-error', { detail: error }));
                        });
                    }
                } catch (error) {
                    console.error('Failed to subscribe to device channel:', error);
                    if (wsStatusEl) {
                        wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Failed to connect</span>';
                    }
                }
            } else {
                console.error('Echo not initialized');
                if (wsStatusEl) {
                    wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Echo not available</span>';
                }
            }
            
            // Load last viewed section or default to terminal
            const lastSection = localStorage.getItem('device-view-section') || 'terminal';
            showSection(lastSection);
        });
    </script>
</x-layouts.app>
