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
                        Note: This sends a command to the Shelly device via HTTP API. Make sure the device is reachable on your network.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Shelly Device ID</label>
                        <p class="mt-1 font-mono text-sm text-neutral-900 dark:text-neutral-100">{{ $device->shelly_device_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Webhook URL</label>
                        <div class="mt-1 p-3 bg-neutral-50 dark:bg-neutral-700/50 rounded-md">
                            <code class="text-xs text-neutral-900 dark:text-neutral-100 break-all">{{ route('api.shelly.webhook', $device->public_id) }}?token={{ $device->shelly_auth_token }}</code>
                        </div>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Configure this URL in your Shelly device's webhook settings
                        </p>
                    </div>

                    @if($device->shelly_last_webhook_at)
                        <div>
                            <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Last Webhook Received</label>
                            <p class="mt-1 text-neutral-900 dark:text-neutral-100">
                                {{ $device->shelly_last_webhook_at->diffForHumans() }}
                                <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                    ({{ $device->shelly_last_webhook_at->format('Y-m-d H:i:s') }})
                                </span>
                            </p>
                        </div>
                    @endif

                    @if($device->shelly_config && isset($device->shelly_config['last_webhook']))
                        <div>
                            <label class="block text-sm font-medium text-neutral-500 dark:text-neutral-400">Last Webhook Data</label>
                            <div class="mt-1 p-3 bg-neutral-50 dark:bg-neutral-700/50 rounded-md overflow-x-auto">
                                <pre class="text-xs text-neutral-900 dark:text-neutral-100">{{ json_encode($device->shelly_config['last_webhook']['payload'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <button 
                        onclick="showUpdateForm()"
                        class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-600">
                        Update Configuration
                    </button>
                    <button 
                        onclick="confirmRemove()"
                        class="ml-2 px-4 py-2 rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-neutral-700 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        Remove Integration
                    </button>
                </div>
            </div>
        @else
            <!-- Setup Form -->
            <div class="space-y-4">
                <p class="text-neutral-600 dark:text-neutral-400">
                    Connect a Shelly device to receive webhook notifications for sensor readings and state changes.
                </p>

                <form id="shelly-setup-form" class="space-y-4">
                    <div>
                        <label for="shelly_device_id" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                            Shelly Device ID
                        </label>
                        <input 
                            type="text" 
                            id="shelly_device_id" 
                            name="shelly_device_id" 
                            placeholder="shellyplug-s-XXXXX"
                            class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Enter your Shelly device identifier (found in device settings)
                        </p>
                    </div>

                    <div>
                        <label for="ip_address" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                            IP Address (optional, for direct control)
                        </label>
                        <input 
                            type="text" 
                            id="ip_address" 
                            name="ip_address" 
                            placeholder="192.168.1.100"
                            pattern="^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$"
                            class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Required for ON/OFF control buttons. Find this in your router's DHCP client list.
                        </p>
                    </div>

                    <button 
                        type="submit"
                        class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Configure Shelly Integration
                    </button>
                </form>
            </div>
        @endif

        <!-- Update Form (hidden by default) -->
        <div id="update-form" class="hidden space-y-4 mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
            <form id="shelly-update-form" class="space-y-4">
                <div>
                    <label for="update_shelly_device_id" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                        Shelly Device ID
                    </label>
                    <input 
                        type="text" 
                        id="update_shelly_device_id" 
                        name="shelly_device_id" 
                        value="{{ $device->shelly_device_id ?? '' }}"
                        class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div class="flex gap-2">
                    <button 
                        type="submit"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">
                        Update
                    </button>
                    <button 
                        type="button"
                        onclick="hideUpdateForm()"
                        class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const devicePublicId = '{{ $device->public_id }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const shellyDeviceId = '{{ $device->shelly_device_id }}';

    // Send command to Shelly device
    async function sendShellyCommand(action) {
        if (!shellyDeviceId) {
            alert('No Shelly device ID configured');
            return;
        }

        try {
            const response = await fetch(`/devices/${devicePublicId}/shelly/control`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ action })
            });
            
            if (response.ok) {
                const result = await response.json();
                alert(`Command sent: ${action.toUpperCase()}\n${result.message || 'Success'}`);
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to send command'));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Setup form handler
    document.getElementById('shelly-setup-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            const response = await fetch(`/devices/${devicePublicId}/shelly/setup`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            
            if (response.ok) {
                const result = await response.json();
                alert('Shelly integration configured successfully!\n\nWebhook URL:\n' + result.webhook_url);
                window.location.reload();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to configure Shelly integration'));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    // Update form handler
    document.getElementById('shelly-update-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            const response = await fetch(`/devices/${devicePublicId}/shelly/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            
            if (response.ok) {
                alert('Shelly configuration updated successfully!');
                window.location.reload();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to update configuration'));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    function showUpdateForm() {
        document.getElementById('update-form').classList.remove('hidden');
    }

    function hideUpdateForm() {
        document.getElementById('update-form').classList.add('hidden');
    }

    async function confirmRemove() {
        if (!confirm('Are you sure you want to remove the Shelly integration? This will delete the webhook configuration.')) {
            return;
        }
        
        try {
            const response = await fetch(`/devices/${devicePublicId}/shelly/remove`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            if (response.ok) {
                alert('Shelly integration removed successfully');
                window.location.reload();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to remove integration'));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
</script>
