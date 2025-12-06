<x-layouts.app title="System Logs">
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">System Logs</h1>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">Zentrale Log-Übersicht aller Devices mit Arduino-Filter</p>
            </div>
            <div class="flex gap-2">
                <button id="clear-logs" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    🗑️ Logs löschen
                </button>
                <button id="refresh-logs" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    🔄 Refresh
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Device Filter -->
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">Device</label>
                    <select id="filter-device" class="w-full px-3 py-2 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Alle Devices</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Level Filter -->
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">Level</label>
                    <select id="filter-level" class="w-full px-3 py-2 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Alle Levels</option>
                        <option value="debug">Debug</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                </div>

                <!-- Pattern Filter (ARDUINO ANTWORT!) -->
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">Pattern</label>
                    <select id="filter-pattern" class="w-full px-3 py-2 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Alle Patterns</option>
                        <option value="Arduino Response">🔧 Arduino Antwort</option>
                        <option value="Status Update">📊 Status Update</option>
                        <option value="Error">❌ Errors</option>
                        <option value="Warning">⚠️ Warnings</option>
                        <option value="Command Execution">⚡ Commands</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">Suche</label>
                    <input type="text" id="filter-search" placeholder="Nach Text suchen..." 
                           class="w-full px-3 py-2 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <div class="text-sm text-neutral-600 dark:text-neutral-400">Total Logs</div>
                <div id="stat-total" class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">-</div>
            </div>
            <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <div class="text-sm text-neutral-600 dark:text-neutral-400">Errors</div>
                <div id="stat-errors" class="text-2xl font-semibold text-red-600">-</div>
            </div>
            <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <div class="text-sm text-neutral-600 dark:text-neutral-400">Warnings</div>
                <div id="stat-warnings" class="text-2xl font-semibold text-amber-600">-</div>
            </div>
            <div class="bg-white dark:bg-neutral-900 rounded-lg border border-green-200 dark:border-green-700/50 p-4 bg-green-50 dark:bg-green-950/20">
                <div class="text-sm text-green-700 dark:text-green-400 font-medium">🔧 Arduino Responses</div>
                <div id="stat-arduino" class="text-2xl font-semibold text-green-600 dark:text-green-400">-</div>
            </div>
            <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                <div class="text-sm text-neutral-600 dark:text-neutral-400">Filtered</div>
                <div id="stat-filtered" class="text-2xl font-semibold text-blue-600">-</div>
            </div>
        </div>

        <!-- Log Table -->
        <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase">Zeit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase">Device</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase">Level</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase">Pattern</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table" class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-neutral-500">
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Logs werden geladen...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
            <div class="text-sm text-neutral-600 dark:text-neutral-400">
                Zeige <span id="showing-from">0</span> - <span id="showing-to">0</span> von <span id="showing-total">0</span>
            </div>
            <div class="flex gap-2">
                <button id="prev-page" class="px-4 py-2 bg-neutral-200 dark:bg-neutral-700 rounded-lg hover:bg-neutral-300 dark:hover:bg-neutral-600 disabled:opacity-50 disabled:cursor-not-allowed transition" disabled>
                    ← Zurück
                </button>
                <span class="px-4 py-2 text-neutral-900 dark:text-neutral-50 font-medium">Seite <span id="current-page">1</span> / <span id="total-pages">1</span></span>
                <button id="next-page" class="px-4 py-2 bg-neutral-200 dark:bg-neutral-700 rounded-lg hover:bg-neutral-300 dark:hover:bg-neutral-600 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    Weiter →
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentPage = 1;
        let totalPages = 1;
        let logPatterns = [];
        let allLogs = [];
        let filteredLogs = [];
        const PER_PAGE = 50;

        // Load log patterns
        async function loadPatterns() {
            try {
                const response = await fetch('/api/log-patterns', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    logPatterns = data.patterns || [];
                    console.log('✅ Loaded', logPatterns.length, 'log patterns');
                } else {
                    console.error('Failed to load patterns:', response.status);
                }
            } catch (error) {
                console.error('Failed to load patterns:', error);
            }
        }

        // Load all logs from ALL devices
        async function loadLogs() {
            try {
                const response = await fetch('/api/logs/all?limit=1000', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                allLogs = data.logs || [];
                applyFilters();
                updateStats();
                console.log('✅ Loaded', allLogs.length, 'logs from', new Set(allLogs.map(l => l.device_id)).size, 'devices');
            } catch (error) {
                console.error('Failed to load logs:', error);
                document.getElementById('logs-table').innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-red-600">
                            ❌ Fehler beim Laden der Logs: ${error.message}
                        </td>
                    </tr>
                `;
            }
        }

        // Apply pattern matching to a log message
        function matchPattern(message) {
            for (const pattern of logPatterns) {
                try {
                    const regex = new RegExp(pattern.regex, 'i');
                    if (regex.test(message)) {
                        return pattern.name;
                    }
                } catch (e) {
                    console.error('Invalid regex:', pattern.regex, e);
                }
            }
            return null;
        }

        // Apply filters
        function applyFilters() {
            const deviceFilter = document.getElementById('filter-device').value;
            const levelFilter = document.getElementById('filter-level').value;
            const patternFilter = document.getElementById('filter-pattern').value;
            const searchFilter = document.getElementById('filter-search').value.toLowerCase();

            filteredLogs = allLogs.filter(log => {
                if (deviceFilter && log.device_id != deviceFilter) return false;
                if (levelFilter && log.level !== levelFilter) return false;
                if (searchFilter && !log.message.toLowerCase().includes(searchFilter)) return false;
                
                if (patternFilter) {
                    const pattern = matchPattern(log.message);
                    if (pattern !== patternFilter) return false;
                }

                return true;
            });

            currentPage = 1;
            renderLogs();
        }

        // Render logs table
        function renderLogs() {
            const start = (currentPage - 1) * PER_PAGE;
            const end = start + PER_PAGE;
            const pageLogs = filteredLogs.slice(start, end);
            totalPages = Math.ceil(filteredLogs.length / PER_PAGE) || 1;

            const tbody = document.getElementById('logs-table');
            
            if (pageLogs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-neutral-500">
                            🔍 Keine Logs gefunden (Filter aktiv?)
                        </td>
                    </tr>
                `;
                updatePagination();
                return;
            }

            tbody.innerHTML = pageLogs.map(log => {
                const pattern = matchPattern(log.message);
                const levelColors = {
                    'debug': 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                    'info': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                    'warning': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                    'error': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                };
                
                const patternBadge = pattern ? `
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${pattern === 'Arduino Response' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'}">
                        ${pattern}
                    </span>
                ` : '<span class="text-neutral-400">-</span>';

                const timestamp = new Date(log.agent_timestamp || log.created_at).toLocaleString('de-DE', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                const deviceName = log.device?.name || `Device ${log.device_id}`;

                return `
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition">
                        <td class="px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100 whitespace-nowrap font-mono">${timestamp}</td>
                        <td class="px-4 py-3 text-sm text-neutral-600 dark:text-neutral-400">${escapeHtml(deviceName)}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded ${levelColors[log.level] || levelColors.debug}">
                                ${log.level.toUpperCase()}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100 font-mono break-words max-w-2xl">${escapeHtml(log.message)}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">${patternBadge}</td>
                    </tr>
                `;
            }).join('');

            updatePagination();
        }

        // Update pagination info
        function updatePagination() {
            const start = filteredLogs.length === 0 ? 0 : (currentPage - 1) * PER_PAGE + 1;
            const end = Math.min(currentPage * PER_PAGE, filteredLogs.length);
            
            document.getElementById('showing-from').textContent = start;
            document.getElementById('showing-to').textContent = end;
            document.getElementById('showing-total').textContent = filteredLogs.length;
            document.getElementById('current-page').textContent = currentPage;
            document.getElementById('total-pages').textContent = totalPages;
            document.getElementById('prev-page').disabled = currentPage === 1;
            document.getElementById('next-page').disabled = currentPage >= totalPages;
            
            // Update filtered stat
            document.getElementById('stat-filtered').textContent = filteredLogs.length;
        }

        // Update stats
        function updateStats() {
            const errors = allLogs.filter(l => l.level === 'error').length;
            const warnings = allLogs.filter(l => l.level === 'warning').length;
            const arduinoLogs = allLogs.filter(l => matchPattern(l.message) === 'Arduino Response').length;

            document.getElementById('stat-total').textContent = allLogs.length;
            document.getElementById('stat-errors').textContent = errors;
            document.getElementById('stat-warnings').textContent = warnings;
            document.getElementById('stat-arduino').textContent = arduinoLogs;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Event listeners
        document.getElementById('filter-device').addEventListener('change', applyFilters);
        document.getElementById('filter-level').addEventListener('change', applyFilters);
        document.getElementById('filter-pattern').addEventListener('change', applyFilters);
        document.getElementById('filter-search').addEventListener('input', applyFilters);
        document.getElementById('refresh-logs').addEventListener('click', loadLogs);
        
        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderLogs();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
        
        document.getElementById('next-page').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderLogs();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
        
        document.getElementById('clear-logs').addEventListener('click', async () => {
            if (!confirm('🗑️ Wirklich ALLE Logs von allen Devices löschen?')) return;
            
            try {
                const response = await fetch('/api/logs/clear', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    alert(`✅ ${data.deleted} Logs gelöscht!`);
                    allLogs = [];
                    applyFilters();
                    updateStats();
                } else {
                    alert('❌ Fehler beim Löschen der Logs');
                }
            } catch (error) {
                console.error('Failed to clear logs:', error);
                alert('❌ Fehler: ' + error.message);
            }
        });

        // Initialize
        (async function init() {
            console.log('🚀 Initializing System Logs...');
            await loadPatterns();
            await loadLogs();
            
            // Auto-refresh every 10 seconds
            setInterval(loadLogs, 10000);
            console.log('✅ Auto-refresh enabled (10s interval)');
        })();
    </script>
    @endpush
</x-layouts.app>
