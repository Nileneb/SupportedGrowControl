<x-layouts.app :title="__('Dashboard')">
    @php
        $devices = auth()->user()->devices;
        $totalDevices = $devices->count();
        $onlineDevices = $devices->where('status', 'online')->count();
        $pairedDevices = $devices->where('status', 'paired')->count();
    @endphp

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
                                                Last seen: {{ $device->last_seen_at->diffForHumans() }}
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
