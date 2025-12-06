<div class="space-y-4">
    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">Shelly Integration</h3>

        @if($device->hasShellyIntegration())
            <!-- Existing Configuration -->
            <div class="space-y-4">
                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="font-medium text-green-800 dark:text-green-300">Shelly integration configured</span>
                    </div>
                </div>

                <!-- Quick Control Section -->
                <div class="p-4 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-700/30">
                    <h4 class="font-medium text-neutral-900 dark:text-neutral-100 mb-3">Quick Control</h4>
                    <div class="flex gap-2">
                        <button
                            onclick="sendShellyCommand('on')"
                            class="flex-1 px-4 py-3 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <span class="mr-2">⚡</span> Turn ON
                        </button>
                        <button
                            onclick="sendShellyCommand('off')"
                            class="flex-1 px-4 py-3 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <span class="mr-2">⭕</span> Turn OFF
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                        @php
                            $shellyDevices = \App\Models\ShellyDevice::where('user_id', auth()->id())->orderBy('name')->get();
                        @endphp

                        <div class="space-y-4">
                            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Shelly Steuerung</h3>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Steuerung via /api/shelly/{id}/{action} (Sanctum).</p>
                                    </div>
                                    <div class="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">API first</div>
                                </div>

                                @if($shellyDevices->isEmpty())
                                    <div class="p-4 rounded-lg border border-dashed border-neutral-300 dark:border-neutral-600 text-neutral-500 dark:text-neutral-400">
                                        Noch keine Shelly-Geräte angelegt. Lege eines per API/Seeder an, um es hier zu steuern.
                                    </div>
                                @else
                                    <div class="grid gap-4 md:grid-cols-2">
                                        @foreach($shellyDevices as $shelly)
                                            <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/60 p-4 space-y-3">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ $shelly->model ?: 'Shelly' }}</div>
                                                        <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">{{ $shelly->name ?? 'Shelly #' . $shelly->id }}</div>
                                                        <div class="text-xs text-neutral-500 dark:text-neutral-500">IP: {{ $shelly->ip_address ?? 'unbekannt' }}</div>
                                                    </div>
                                                    <div class="text-right text-xs text-neutral-500 dark:text-neutral-400">
                                                        <div>Last seen: {{ $shelly->last_seen_at?->diffForHumans() ?? '–' }}</div>
                                                        <div>Webhook: {{ $shelly->last_webhook_at?->diffForHumans() ?? '–' }}</div>
                                                    </div>
                                                </div>

                                                <div class="flex gap-2">
                                                    <button data-action="on" data-shelly-id="{{ $shelly->id }}" class="shelly-action flex-1 px-3 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">An</button>
                                                    <button data-action="off" data-shelly-id="{{ $shelly->id }}" class="shelly-action flex-1 px-3 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Aus</button>
                                                    <button data-action="toggle" data-shelly-id="{{ $shelly->id }}" class="shelly-action flex-1 px-3 py-2 rounded-lg bg-neutral-700 text-white text-sm font-medium hover:bg-neutral-800">Toggle</button>
                                                </div>

                                                <div class="text-xs text-neutral-500 dark:text-neutral-400 space-y-1">
                                                    <div>Webhook URL:</div>
                                                    <code class="break-all block">{{ route('api.shelly.webhook', ['shelly' => $shelly->id]) }}?token={{ $shelly->auth_token }}</code>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <script>
                            document.querySelectorAll('.shelly-action').forEach(btn => {
                                btn.addEventListener('click', async (e) => {
                                    const shellyId = e.currentTarget.dataset.shellyId;
                                    const action = e.currentTarget.dataset.action;
                                    try {
                                        const res = await fetch(`/api/shelly/${shellyId}/${action}`, {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                                'Accept': 'application/json'
                                            },
                                        });
                                        const json = await res.json();
                                        if (!res.ok || json.success === false) {
                                            alert(`Fehler bei ${action.toUpperCase()}: ${json.error || json.message || 'Unknown error'}`);
                                        } else {
                                            alert(`${action.toUpperCase()} ausgeführt`);
                                        }
                                    } catch (err) {
                                        alert(`Fehler: ${err.message}`);
                                    }
                                });
                            });
                        </script>
                        </p>
