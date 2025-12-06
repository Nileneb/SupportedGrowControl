<x-layouts.app :title="$device->name">
            <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-400">Device Overview</p>
                        <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">{{ $device->name }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-neutral-600 dark:text-neutral-300">
                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium
                                @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300
                                @else bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 @endif">
                                <span class="h-2 w-2 rounded-full @if($device->status === 'online') bg-green-500 @else bg-neutral-400 @endif"></span>
                                {{ ucfirst($device->status) }}
                            </span>
                            @if($device->last_seen_at)
                                <span>Last seen {{ $device->last_seen_at->diffForHumans() }}</span>
                            @else
                                <span>Last seen: never</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs sm:justify-end">
                        <span class="rounded-full bg-neutral-100 px-3 py-1 font-mono text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">Public ID: {{ $device->public_id }}</span>
                        @if($device->bootstrap_id)
                            <span class="rounded-full bg-neutral-100 px-3 py-1 font-mono text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">Bootstrap: {{ $device->bootstrap_id }}</span>
                        @endif
                        <form method="POST" action="{{ route('devices.destroy', $device->public_id) }}" onsubmit="return confirm('Device wirklich löschen? Dieser Schritt kann nicht rückgängig gemacht werden.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40 text-xs">
                                Delete Device
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Device Information</h2>
                        </div>
                        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Device Name</dt>
                                <dd class="mt-1 text-neutral-900 dark:text-neutral-50">{{ $device->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Bootstrap ID</dt>
                                <dd class="mt-1 font-mono text-neutral-800 dark:text-neutral-200">{{ $device->bootstrap_id ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium
                                        @if($device->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300
                                        @else bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 @endif">
                                        <span class="h-2 w-2 rounded-full @if($device->status === 'online') bg-green-500 @else bg-neutral-400 @endif"></span>
                                        {{ ucfirst($device->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Last Seen</dt>
                                <dd class="mt-1 text-neutral-900 dark:text-neutral-50">
                                    @if($device->last_seen_at)
                                        {{ $device->last_seen_at->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Platform</dt>
                                <dd class="mt-1 text-neutral-900 dark:text-neutral-50">{{ $device->device_info['platform'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Version</dt>
                                <dd class="mt-1 text-neutral-900 dark:text-neutral-50">{{ $device->device_info['version'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Paired At</dt>
                                <dd class="mt-1 text-neutral-900 dark:text-neutral-50">{{ optional($device->paired_at)?->format('Y-m-d H:i') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 dark:text-neutral-400">Created</dt>
                                <dd class="mt-1 text-neutral-900 dark:text-neutral-50">{{ optional($device->created_at)?->format('Y-m-d H:i') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Serial Console</h2>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Sende Befehle und sieh dir die unmittelbaren Antworten an.</p>
                            </div>
                            <span id="serial-ws-status" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Connecting...</span>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div id="serial-console" class="h-80 overflow-y-auto rounded-lg border border-neutral-200 bg-neutral-950 p-4 font-mono text-xs text-green-300 shadow-inner dark:border-neutral-700">
                                <div class="text-neutral-500">Waiting for device output...</div>
                            </div>

                            <form id="serial-command-form" class="space-y-2">
                                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-200">Command</label>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <input
                                        id="serial-command-input"
                                        type="text"
                                        autocomplete="off"
                                        placeholder="Status, TDS, Spray 1000, FillL 2.0 ..."
                                        class="flex-1 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100" />
                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 disabled:opacity-60">
                                        Send
                                    </button>
                                </div>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">Tipps: Status, TDS, Spray &lt;ms&gt;, FillL &lt;liters&gt;, SprayOn, SprayOff</p>
                            </form>

                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-neutral-800 dark:text-neutral-100">Command History</h3>
                                    <button id="clear-history" type="button" class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">Clear</button>
                                </div>
                                <div id="command-history" class="space-y-1 max-h-32 overflow-y-auto text-xs"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                const deviceId = '{{ $device->public_id }}';
                const devicePublicId = '{{ $device->public_id }}';
                window.deviceId = deviceId;
                window.devicePublicId = devicePublicId;

                const wsStatusEl = document.getElementById('serial-ws-status');
                const serialConsole = document.getElementById('serial-console');
                const commandInput = document.getElementById('serial-command-input');
                const commandForm = document.getElementById('serial-command-form');
                const historyContainer = document.getElementById('command-history');
                const clearHistoryBtn = document.getElementById('clear-history');

                let serialLogCount = 0;
                const MAX_SERIAL_LOGS = 500;
                let commandHistory = [];
                const MAX_HISTORY = 30;

                function setWsStatus(label, tone) {
                    const tones = {
                        success: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        warn: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                        error: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        muted: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200'
                    };
                    wsStatusEl.className = `rounded-full px-3 py-1 text-xs font-semibold ${tones[tone] || tones.muted}`;
                    wsStatusEl.textContent = label;
                }

                // Enhanced addSerialLog to support timestamps and parsing
                function addSerialLog(message, source = 'device', timestamp = null) {
                    if (!serialConsole) return;
                    const ts = timestamp ? new Date(timestamp).toLocaleTimeString() : new Date().toLocaleTimeString();
                    const prefix = source === 'user' ? '→' : '←';
                    const color = source === 'user' ? 'text-blue-300' : 'text-green-300';

                    if (serialLogCount === 0) {
                        serialConsole.innerHTML = '';
                    }

                    const row = document.createElement('div');
                    row.className = `${color} mb-1`;
                    row.innerHTML = `<span class="text-neutral-500">[${ts}]</span> ${prefix} ${escapeHtml(message)}`;
                    serialConsole.appendChild(row);
                    serialLogCount++;

                    if (serialLogCount > MAX_SERIAL_LOGS) {
                        serialConsole.removeChild(serialConsole.firstChild);
                        serialLogCount--;
                    }

                    serialConsole.scrollTop = serialConsole.scrollHeight;
                }

                function renderCommandHistory() {
                    if (!historyContainer) return;
                    if (commandHistory.length === 0) {
                        historyContainer.innerHTML = '<div class="text-neutral-500 dark:text-neutral-400">No commands yet</div>';
                        return;
                    }

                    const tone = {
                        pending: 'text-amber-600 dark:text-amber-300',
                        executing: 'text-blue-600 dark:text-blue-300',
                        success: 'text-green-600 dark:text-green-300',
                        failed: 'text-red-600 dark:text-red-300'
                    };

                    historyContainer.innerHTML = commandHistory.map(cmd => {
                        const statusTone = tone[cmd.status] || tone.pending;
                        return `<div class="flex items-center justify-between rounded border border-neutral-200 bg-neutral-50 px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800">
                            <div class="flex items-center gap-2">
                                <span class="${statusTone}">${cmd.status}</span>
                                <span class="font-mono text-neutral-900 dark:text-neutral-100">${escapeHtml(cmd.command)}</span>
                            </div>
                            <span class="text-neutral-500 dark:text-neutral-400">${cmd.timestamp.toLocaleTimeString()}</span>
                        </div>`;
                    }).join('');
                }

                function updateCommandHistory(event) {
                    const cmd = commandHistory.find(c => c.id === event.command_id);
                    if (cmd) {
                        cmd.status = event.status || cmd.status;
                        cmd.result = event.result_message;
                        renderCommandHistory();
                        if (event.result_message) {
                            addSerialLog(event.result_message, 'device');
                        }
                    }
                }

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                commandForm?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const command = commandInput.value.trim();
                    if (!command) return;

                    try {
                        const response = await fetch(`/api/growdash/devices/${devicePublicId}/commands`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                type: 'serial_command',
                                params: { command }
                            })
                        });

                        if (!response.ok) {
                            const error = await response.json().catch(() => ({}));
                            addSerialLog(`Error: ${error.message || 'Failed to send command'}`, 'user');
                            return;
                        }

                        const data = await response.json();
                        addSerialLog(command, 'user');
                        commandInput.value = '';

                        commandHistory.unshift({
                            id: data.command_id,
                            command,
                            status: 'pending',
                            timestamp: new Date()
                        });
                        if (commandHistory.length > MAX_HISTORY) {
                            commandHistory.pop();
                        }
                        renderCommandHistory();
                    } catch (error) {
                        addSerialLog(`Error: ${error.message}`, 'user');
                    }
                });

                clearHistoryBtn?.addEventListener('click', () => {
                    commandHistory = [];
                    renderCommandHistory();
                });

                // Load historical logs from database
                async function loadHistoricalLogs() {
                    try {
                        const response = await fetch(`/api/devices/${deviceId}/logs?limit=100`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            credentials: 'same-origin' // wichtig: Cookies mitschicken!
                        });

                        if (!response.ok) {
                            console.error('Failed to load historical logs:', response.statusText);
                            return;
                        }

                        const data = await response.json();
                        if (data.logs && data.logs.length > 0) {
                            // Display logs in reverse chronological order (oldest first)
                            data.logs.reverse().forEach(log => {
                                processAndDisplayLog(log.message, 'device', log.created_at);
                            });
                        }
                    } catch (error) {
                        console.error('Error loading historical logs:', error);
                    }
                }

                // Dynamic log patterns loaded from database
                let logPatterns = [];

                // Load log patterns from API
                async function loadLogPatterns() {
                    try {
                        const response = await fetch('/api/log-patterns', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        });

                        if (!response.ok) {
                            console.error('Failed to load log patterns:', response.statusText);
                            return;
                        }

                        const data = await response.json();
                        if (data.patterns) {
                            // Convert regex strings to RegExp objects
                            logPatterns = data.patterns.map(pattern => ({
                                name: pattern.name,
                                regex: new RegExp(pattern.regex.slice(1, pattern.regex.lastIndexOf('/')), 
                                                pattern.regex.slice(pattern.regex.lastIndexOf('/') + 1)),
                                icon: pattern.icon,
                                color: pattern.color,
                                parser_config: pattern.parser_config,
                                priority: pattern.priority,
                                format: (matches) => {
                                    const result = {
                                        type: pattern.name.toLowerCase().replace(/\s+/g, '_'),
                                        raw: matches[0],
                                        icon: pattern.icon || '',
                                        color: pattern.color || 'text-green-300'
                                    };

                                    // Extract key-value pairs if configured
                                    if (pattern.parser_config?.extractor === 'key_value_pairs') {
                                        const data = matches[1] || matches[0];
                                        const kvRegex = /(\w+)=([\d.]+)/g;
                                        const parsed = {};
                                        let match;
                                        while ((match = kvRegex.exec(data)) !== null) {
                                            parsed[match[1]] = match[2];
                                        }
                                        result.parsed = parsed;
                                        result.display = `${result.icon} ${pattern.name}: ${Object.entries(parsed).map(([k,v]) => `${k}=${v}`).join(' | ')}`;
                                    } else {
                                        // Use second capture group if available, otherwise full match
                                        const text = matches[2] || matches[1] || matches[0];
                                        result.display = `${result.icon} ${pattern.name}: ${text}`;
                                    }

                                    return result;
                                }
                            }));
                            console.log(`✅ Loaded ${logPatterns.length} log patterns from database`);
                        }
                    } catch (error) {
                        console.error('Error loading log patterns:', error);
                        // Fallback to empty patterns array
                        logPatterns = [];
                    }
                }

                // Process log with regex patterns and display
                function processAndDisplayLog(message, source = 'device', timestamp = null) {
                    let parsed = null;

                    // Try to match against known patterns
                    for (const pattern of logPatterns) {
                        const match = message.match(pattern.regex);
                        if (match) {
                            parsed = pattern.format(match);
                            break;
                        }
                    }

                    if (parsed) {
                        addSerialLog(parsed.display || message, source, timestamp);
                    } else {
                        // Display raw message if no pattern matches
                        addSerialLog(message, source, timestamp);
                    }
                }

                document.addEventListener('DOMContentLoaded', async () => {
                    renderCommandHistory();
                    
                    // Load patterns first, then historical logs
                    await loadLogPatterns();
                    await loadHistoricalLogs();
                    
                    if (!window.Echo) {
                        setWsStatus('No WebSocket', 'error');
                        return;
                    }

                    try {
                        const channel = window.Echo.private(`device.${deviceId}`)
                            .listen('device.log.received', (event) => {
                                // Real-time logs from Agent (Arduino Serial Monitor, etc.)
                                if (event.message) {
                                    processAndDisplayLog(event.message, 'device');
                                }
                            })
                            .listen('device.command.status.updated', (event) => updateCommandHistory(event));

                        const connection = window.Echo.connector?.pusher?.connection;
                        if (connection) {
                            connection.bind('connected', () => setWsStatus('Connected', 'success'));
                            connection.bind('disconnected', () => setWsStatus('Disconnected', 'muted'));
                            connection.bind('error', () => setWsStatus('Error', 'error'));
                        } else {
                            setWsStatus('Connected', 'success');
                        }
                    } catch (err) {
                        console.error('WebSocket init failed', err);
                        setWsStatus('Failed', 'error');
                    }
                });
            </script>
</x-layouts.app>
