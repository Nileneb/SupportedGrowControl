<x-layouts.app :title="$device->name">
    <style>
        [data-section] {
            resize: both;
            overflow: auto;
            min-width: 300px;
            min-height: 300px;
        }
        
        .workspace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1rem;
            grid-auto-rows: 500px;
            grid-auto-flow: dense;
        }
        
        .workspace-item {
            position: relative;
            display: flex;
            flex-direction: column;
            border-radius: 0.5rem;
            border: 1px solid rgb(229, 231, 235);
            background: white;
            overflow: hidden;
        }
        
        .workspace-item.dark\:bg-neutral-800 {
            border-color: rgb(63, 63, 70);
            background: rgb(24, 24, 27);
        }
        
        .workspace-item.tall { grid-row: span 2; }
        .workspace-item.wide { grid-column: span 2; }
        
        .workspace-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid inherit;
            background: rgb(249, 250, 251);
            cursor: grab;
            user-select: none;
        }
        
        .workspace-header.dark\:bg-neutral-700 {
            background: rgb(55, 65, 81);
        }
        
        .workspace-header:active {
            cursor: grabbing;
        }
        
        .workspace-body {
            flex: 1;
            overflow: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
        }
        
        .workspace-action-btn {
            width: 24px;
            height: 24px;
            padding: 0;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        
        .workspace-action-btn:hover {
            opacity: 1;
        }
        
        @media (max-width: 1024px) {
            .workspace-grid {
                grid-template-columns: 1fr;
            }
            .workspace-item.wide {
                grid-column: span 1;
            }
        }
    </style>

    <div class="flex h-full w-full flex-1 gap-4 p-4">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar-container w-64 flex-shrink-0 space-y-2 transition-all duration-300">
            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-4">
                <!-- Device Header -->
                <div class="mb-4">
                    <h2 class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $device->name }}</h2>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ $device->bootstrap_id }}</p>
                </div>

                <!-- Device Status -->
                <div class="mb-4 flex items-center gap-2">
                    <span class="px-3 py-1 text-xs font-medium rounded-full
                        @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                        @else bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400
                        @endif">
                        {{ ucfirst($device->status) }}
                    </span>
                    @if($device->last_seen_at)
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ $device->last_seen_at->diffForHumans() }}
                        </span>
                    @endif
                </div>

                <!-- WebSocket Status -->
                <div id="ws-status" class="mb-4 text-xs">
                    <span class="text-yellow-600 dark:text-yellow-400">⏳ Connecting...</span>
                </div>

                <!-- Workspace Sections -->
                <div class="mb-4 space-y-2 border-t border-neutral-200 dark:border-neutral-700 pt-4">
                    <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">Sections</p>
                    
                    <div class="space-y-1">
                        <!-- Terminal -->
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-700">
                            <input type="checkbox" data-section-toggle="terminal" class="section-toggle" checked>
                            <span class="text-sm text-neutral-700 dark:text-neutral-300">💻 Terminal</span>
                        </label>

                        <!-- Sensors -->
                        @if(!empty($sensors))
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-700">
                            <input type="checkbox" data-section-toggle="sensors" class="section-toggle" checked>
                            <span class="text-sm text-neutral-700 dark:text-neutral-300">📊 Sensors ({{ count($sensors) }})</span>
                        </label>
                        @endif

                        <!-- Actuators -->
                        @if(!empty($actuators))
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-700">
                            <input type="checkbox" data-section-toggle="actuators" class="section-toggle" checked>
                            <span class="text-sm text-neutral-700 dark:text-neutral-300">⚙️ Actuators ({{ count($actuators) }})</span>
                        </label>
                        @endif

                        <!-- Device Info -->
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-700">
                            <input type="checkbox" data-section-toggle="info" class="section-toggle">
                            <span class="text-sm text-neutral-700 dark:text-neutral-300">ℹ️ Device Info</span>
                        </label>

                        <!-- Logs -->
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-700">
                            <input type="checkbox" data-section-toggle="logs" class="section-toggle">
                            <span class="text-sm text-neutral-700 dark:text-neutral-300">📝 Logs</span>
                        </label>

                        <!-- Shelly Integration -->
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-700">
                            <input type="checkbox" data-section-toggle="shelly" class="section-toggle">
                            <span class="text-sm text-neutral-700 dark:text-neutral-300">🔌 Shelly</span>
                        </label>
                    </div>
                </div>

                <!-- Workspace Controls -->
                <div class="border-t border-neutral-200 dark:border-neutral-700 pt-4 space-y-2">
                    <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">Workspace</p>
                    
                    <button id="reset-layout-btn" class="w-full px-3 py-2 text-sm text-left rounded bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-600">
                        🔄 Reset Layout
                    </button>
                    
                    <button id="export-config-btn" class="w-full px-3 py-2 text-sm text-left rounded bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-neutral-600">
                        💾 Export Config
                    </button>
                </div>

                <!-- Quick Actions -->
                @if($device->status === 'online')
                <div class="border-t border-neutral-200 dark:border-neutral-700 pt-4 space-y-2">
                    <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">Quick Actions</p>
                    
                    <button id="quick-refresh-btn" class="w-full px-3 py-2 text-sm text-left rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50">
                        🔄 Refresh
                    </button>
                    
                    <button id="quick-reconnect-btn" class="w-full px-3 py-2 text-sm text-left rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/50">
                        ⚡ Reconnect
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="flex-1 min-w-0 overflow-hidden flex flex-col">
            <div class="workspace-grid" id="workspace-grid">
                <!-- Terminal -->
                <div id="section-terminal" class="workspace-item" data-section="terminal">
                    <div class="workspace-header dark:bg-neutral-700" id="terminal-header">
                        <span class="text-sm font-medium">💻 Terminal</span>
                        <div class="flex gap-1">
                            <button class="workspace-action-btn section-minimize-btn" title="Minimize">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </button>
                            <button class="workspace-action-btn section-close-btn" title="Close">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="workspace-body">
                        @include('devices.sections.terminal', ['device' => $device])
                    </div>
                </div>

                <!-- Sensors -->
                @if(!empty($sensors))
                <div id="section-sensors" class="workspace-item hidden" data-section="sensors">
                    <div class="workspace-header dark:bg-neutral-700" id="sensors-header">
                        <span class="text-sm font-medium">📊 Sensors ({{ count($sensors) }})</span>
                        <div class="flex gap-1">
                            <button class="workspace-action-btn section-minimize-btn" title="Minimize">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </button>
                            <button class="workspace-action-btn section-close-btn" title="Close">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="workspace-body">
                        @include('devices.sections.sensors', ['device' => $device, 'sensors' => $sensors, 'sensorReadings' => $sensorReadings])
                    </div>
                </div>
                @endif

                <!-- Actuators -->
                @if(!empty($actuators))
                <div id="section-actuators" class="workspace-item hidden" data-section="actuators">
                    <div class="workspace-header dark:bg-neutral-700" id="actuators-header">
                        <span class="text-sm font-medium">⚙️ Actuators ({{ count($actuators) }})</span>
                        <div class="flex gap-1">
                            <button class="workspace-action-btn section-minimize-btn" title="Minimize">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </button>
                            <button class="workspace-action-btn section-close-btn" title="Close">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="workspace-body">
                        @include('devices.sections.actuators', ['device' => $device, 'actuators' => $actuators])
                    </div>
                </div>
                @endif

                <!-- Device Info -->
                <div id="section-info" class="workspace-item hidden" data-section="info">
                    <div class="workspace-header dark:bg-neutral-700" id="info-header">
                        <span class="text-sm font-medium">ℹ️ Device Info</span>
                        <div class="flex gap-1">
                            <button class="workspace-action-btn section-minimize-btn" title="Minimize">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </button>
                            <button class="workspace-action-btn section-close-btn" title="Close">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="workspace-body">
                        @include('devices.sections.info', ['device' => $device])
                    </div>
                </div>

                <!-- Logs -->
                <div id="section-logs" class="workspace-item hidden" data-section="logs">
                    <div class="workspace-header dark:bg-neutral-700" id="logs-header">
                        <span class="text-sm font-medium">📝 Logs</span>
                        <div class="flex gap-1">
                            <button class="workspace-action-btn section-minimize-btn" title="Minimize">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </button>
                            <button class="workspace-action-btn section-close-btn" title="Close">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="workspace-body">
                        <div id="device-logs-container">
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">Loading logs...</div>
                        </div>
                    </div>
                </div>

                <!-- Shelly Integration -->
                <div id="section-shelly" class="workspace-item hidden" data-section="shelly">
                    <div class="workspace-header dark:bg-neutral-700" id="shelly-header">
                        <span class="text-sm font-medium">🔌 Shelly Integration</span>
                        <div class="flex gap-1">
                            <button class="workspace-action-btn section-minimize-btn" title="Minimize">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </button>
                            <button class="workspace-action-btn section-close-btn" title="Close">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="workspace-body">
                        @include('devices.sections.shelly', ['device' => $device])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const DEVICE_ID = {{ $device->id }};
        const DEVICE_PUBLIC_ID = '{{ $device->public_id }}';
        const STORAGE_KEY = `workspace-${DEVICE_ID}`;
        const MINIMIZE_KEY = `minimize-${DEVICE_ID}`;

        // ==================== Workspace State Management ====================
        class WorkspaceManager {
            constructor() {
                this.state = this.loadState();
                this.minimizedSections = this.loadMinimized();
            }

            loadState() {
                const saved = localStorage.getItem(STORAGE_KEY);
                return saved ? JSON.parse(saved) : this.getDefaultState();
            }

            getDefaultState() {
                return {
                    visibleSections: ['terminal', 'sensors', 'actuators'],
                    sectionOrder: [],
                    gridLayout: {},
                    lastUpdated: new Date().toISOString()
                };
            }

            loadMinimized() {
                const saved = localStorage.getItem(MINIMIZE_KEY);
                return saved ? JSON.parse(saved) : [];
            }

            saveState() {
                this.state.lastUpdated = new Date().toISOString();
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.state));
            }

            saveMinimized() {
                localStorage.setItem(MINIMIZE_KEY, JSON.stringify(this.minimizedSections));
            }

            toggleSection(sectionName, isVisible) {
                if (isVisible && !this.state.visibleSections.includes(sectionName)) {
                    this.state.visibleSections.push(sectionName);
                } else if (!isVisible && this.state.visibleSections.includes(sectionName)) {
                    this.state.visibleSections = this.state.visibleSections.filter(s => s !== sectionName);
                }
                this.saveState();
            }

            toggleMinimize(sectionName) {
                const idx = this.minimizedSections.indexOf(sectionName);
                if (idx > -1) {
                    this.minimizedSections.splice(idx, 1);
                } else {
                    this.minimizedSections.push(sectionName);
                }
                this.saveMinimized();
            }

            reset() {
                localStorage.removeItem(STORAGE_KEY);
                localStorage.removeItem(MINIMIZE_KEY);
                this.state = this.getDefaultState();
                this.minimizedSections = [];
                window.location.reload();
            }

            export() {
                return JSON.stringify({
                    workspace: this.state,
                    minimized: this.minimizedSections,
                    exportedAt: new Date().toISOString()
                }, null, 2);
            }
        }

        const workspace = new WorkspaceManager();

        // ==================== Section Toggle Handlers ====================
        document.querySelectorAll('.section-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const section = e.target.dataset.sectionToggle;
                const element = document.getElementById(`section-${section}`);
                
                if (e.target.checked) {
                    element.classList.remove('hidden');
                    workspace.toggleSection(section, true);
                } else {
                    element.classList.add('hidden');
                    workspace.toggleSection(section, false);
                }
            });
        });

        // ==================== Close/Minimize Buttons ====================
        document.querySelectorAll('.section-close-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const item = e.target.closest('.workspace-item');
                const section = item.dataset.section;
                const toggle = document.querySelector(`[data-section-toggle="${section}"]`);
                
                item.classList.add('hidden');
                toggle.checked = false;
                workspace.toggleSection(section, false);
            });
        });

        document.querySelectorAll('.section-minimize-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const item = e.target.closest('.workspace-item');
                const section = item.dataset.section;
                const body = item.querySelector('.workspace-body');
                
                workspace.toggleMinimize(section);
                item.classList.toggle('h-12');
                body.classList.toggle('hidden');
            });
        });

        // ==================== Workspace Controls ====================
        document.getElementById('reset-layout-btn').addEventListener('click', () => {
            if (confirm('Reset workspace to default layout? Current configuration will be lost.')) {
                workspace.reset();
            }
        });

        document.getElementById('export-config-btn').addEventListener('click', () => {
            const config = workspace.export();
            const blob = new Blob([config], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `device-${DEVICE_ID}-workspace-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            URL.revokeObjectURL(url);
        });

        // ==================== Quick Actions ====================
        document.getElementById('quick-refresh-btn')?.addEventListener('click', () => {
            console.log('Refreshing device data...');
            // Reload terminal output and sensor readings
            window.dispatchEvent(new CustomEvent('device-refresh'));
        });

        document.getElementById('quick-reconnect-btn')?.addEventListener('click', () => {
            console.log('Reconnecting to device...');
            if (window.Echo) {
                window.Echo.leave(`device.${DEVICE_ID}`);
                setTimeout(() => {
                    window.Echo.private(`device.${DEVICE_ID}`).listen('DeviceTelemetryReceived', (event) => {
                        window.dispatchEvent(new CustomEvent('device-telemetry', { detail: event }));
                    });
                }, 500);
            }
        });

        // ==================== WebSocket & Event Listeners ====================
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize WebSocket
            let wsConnected = false;
            const wsStatusEl = document.getElementById('ws-status');

            if (window.Echo) {
                try {
                    window.Echo.private(`device.${DEVICE_ID}`)
                        .listen('DeviceTelemetryReceived', (event) => {
                            window.dispatchEvent(new CustomEvent('device-telemetry', { detail: event }));
                        })
                        .listen('CommandStatusUpdated', (event) => {
                            window.dispatchEvent(new CustomEvent('command-status', { detail: event }));
                        })
                        .listen('DeviceCapabilitiesUpdated', (event) => {
                            window.dispatchEvent(new CustomEvent('device-capabilities', { detail: event }));
                        });

                    if (window.Echo.connector?.pusher?.connection) {
                        window.Echo.connector.pusher.connection.bind('connected', () => {
                            wsConnected = true;
                            wsStatusEl.innerHTML = '<span class="text-green-600 dark:text-green-400">✓ Connected</span>';
                            window.dispatchEvent(new CustomEvent('ws-connected'));
                        });

                        window.Echo.connector.pusher.connection.bind('disconnected', () => {
                            wsConnected = false;
                            wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Disconnected</span>';
                            window.dispatchEvent(new CustomEvent('ws-disconnected'));
                        });

                        window.Echo.connector.pusher.connection.bind('error', (error) => {
                            wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Error</span>';
                            window.dispatchEvent(new CustomEvent('ws-error', { detail: error }));
                        });
                    }
                } catch (error) {
                    console.error('WebSocket initialization error:', error);
                    wsStatusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">✗ Failed</span>';
                }
            }

            // Restore minimized sections
            workspace.minimizedSections.forEach(section => {
                const item = document.getElementById(`section-${section}`);
                if (item) {
                    const body = item.querySelector('.workspace-body');
                    item.classList.add('h-12');
                    body.classList.add('hidden');
                }
            });

            // Load logs on demand
            const logsSection = document.getElementById('section-logs');
            if (logsSection && !logsSection.classList.contains('hidden')) {
                loadDeviceLogs();
            }

            logsSection?.addEventListener('click', () => {
                if (!logsSection.classList.contains('hidden') && !logsSection.querySelector('.log-entry')) {
                    loadDeviceLogs();
                }
            });
        });

        async function loadDeviceLogs() {
            const container = document.getElementById('device-logs-container');
            if (!container) return;

            try {
                const response = await fetch(`/api/devices/${DEVICE_ID}/logs?limit=50`);
                const data = await response.json();

                if (data.logs && data.logs.length > 0) {
                    container.innerHTML = data.logs.map(log => `
                        <div class="log-entry mb-2 pb-2 border-b border-neutral-200 dark:border-neutral-700">
                            <div class="text-xs text-neutral-500 dark:text-neutral-400">${new Date(log.created_at).toLocaleString()}</div>
                            <div class="text-sm text-neutral-900 dark:text-neutral-100">${log.message}</div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="text-sm text-neutral-500 dark:text-neutral-400">No logs available</div>';
                }
            } catch (error) {
                container.innerHTML = `<div class="text-sm text-red-600 dark:text-red-400">Failed to load logs: ${error.message}</div>`;
            }
        }
    </script>
</x-layouts.app>
