<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-neutral-900 dark:text-neutral-100">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-semibold">Shelly Devices</h2>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                            Manage your Shelly smart devices
                        </p>
                    </div>
                    <button 
                        wire:click="toggleAddForm"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ $showAddForm ? 'Cancel' : '+ Add Shelly Device' }}
                    </button>
                </div>

                <!-- Flash Messages -->
                @if (session()->has('success'))
                    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-300">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-300">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Add Form -->
                @if($showAddForm)
                    <div class="mb-6 p-6 bg-neutral-50 dark:bg-neutral-700/30 rounded-lg border border-neutral-200 dark:border-neutral-600">
                        <h3 class="text-lg font-medium mb-4">Add New Shelly Device</h3>
                        <form wire:submit.prevent="save" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium mb-1">Device Name</label>
                                    <input 
                                        type="text" 
                                        id="name"
                                        wire:model="name"
                                        placeholder="Kitchen Light"
                                        class="w-full rounded-md border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100"
                                        required>
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="shellyDeviceId" class="block text-sm font-medium mb-1">Shelly Device ID</label>
                                    <input 
                                        type="text" 
                                        id="shellyDeviceId"
                                        wire:model="shellyDeviceId"
                                        placeholder="shellyplug-s-XXXXX"
                                        class="w-full rounded-md border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100"
                                        required>
                                    @error('shellyDeviceId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="ipAddress" class="block text-sm font-medium mb-1">IP Address</label>
                                    <input 
                                        type="text" 
                                        id="ipAddress"
                                        wire:model="ipAddress"
                                        placeholder="192.168.1.100"
                                        pattern="^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$"
                                        class="w-full rounded-md border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100"
                                        required>
                                    @error('ipAddress') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="model" class="block text-sm font-medium mb-1">Model (optional)</label>
                                    <input 
                                        type="text" 
                                        id="model"
                                        wire:model="model"
                                        placeholder="Shelly Plug S, Shelly Plus 1PM, etc."
                                        class="w-full rounded-md border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100">
                                    @error('model') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Add Device
                            </button>
                        </form>
                    </div>
                @endif

                <!-- Devices List -->
                @if(count($shellies) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($shellies as $shelly)
                            <div class="p-4 border border-neutral-200 dark:border-neutral-700 rounded-lg bg-neutral-50 dark:bg-neutral-700/30">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-lg">{{ $shelly->name }}</h3>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400 font-mono">{{ $shelly->shelly_device_id }}</p>
                                        @if($shelly->model)
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">{{ $shelly->model }}</p>
                                        @endif
                                    </div>
                                    <button 
                                        wire:click="delete({{ $shelly->id }})"
                                        wire:confirm="Are you sure you want to delete this Shelly device?"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-2 mb-3">
                                    <div class="flex items-center text-sm">
                                        <span class="text-neutral-500 dark:text-neutral-400 w-16">IP:</span>
                                        <span class="font-mono">{{ $shelly->ip_address ?? '—' }}</span>
                                    </div>
                                    @if($shelly->last_seen_at)
                                        <div class="flex items-center text-sm">
                                            <span class="text-neutral-500 dark:text-neutral-400 w-16">Seen:</span>
                                            <span>{{ $shelly->last_seen_at->diffForHumans() }}</span>
                                        </div>
                                    @endif
                                    @if($shelly->device)
                                        <div class="flex items-center text-sm">
                                            <span class="text-neutral-500 dark:text-neutral-400 w-16">Linked:</span>
                                            <a href="/devices/{{ $shelly->device->public_id }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ $shelly->device->name }}
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    <button 
                                        wire:click="turnOn({{ $shelly->id }})"
                                        class="flex-1 px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                        ON
                                    </button>
                                    <button 
                                        wire:click="turnOff({{ $shelly->id }})"
                                        class="flex-1 px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                        OFF
                                    </button>
                                </div>

                                <div class="mt-3 pt-3 border-t border-neutral-200 dark:border-neutral-600">
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200">
                                            Webhook URL
                                        </summary>
                                        <div class="mt-2 p-2 bg-white dark:bg-neutral-800 rounded border border-neutral-200 dark:border-neutral-600">
                                            <code class="text-xs break-all">{{ $shelly->getWebhookUrl() }}</code>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">No Shelly devices</h3>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Get started by adding your first Shelly device.</p>
                        <div class="mt-6">
                            <button 
                                wire:click="toggleAddForm"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                + Add Shelly Device
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
