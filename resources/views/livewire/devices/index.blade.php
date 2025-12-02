<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <flux:heading size="xl">My Devices</flux:heading>
    <flux:subheading>Manage and monitor your connected GrowDash devices</flux:subheading>

    <div class="mt-6">
        @if($devices->isEmpty())
            <flux:card class="text-center py-12">
                <flux:icon.cube-transparent class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No devices</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by pairing a new device.</p>
                <div class="mt-6">
                    <flux:button href="{{ route('devices.pair') }}" variant="primary">
                        Pair New Device
                    </flux:button>
                </div>
            </flux:card>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($devices as $device)
                    <flux:card>
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">{{ $device->name }}</flux:heading>
                            <flux:badge :variant="$device->status === 'online' ? 'success' : 'muted'">
                                {{ ucfirst($device->status ?? 'unknown') }}
                            </flux:badge>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-gray-600">
                            @if($device->board_type)
                                <div class="flex items-center">
                                    <flux:icon.cpu-chip class="h-4 w-4 mr-2" />
                                    <span>{{ ucfirst(str_replace('_', ' ', $device->board_type)) }}</span>
                                </div>
                            @endif

                            @if($device->last_seen_at)
                                <div class="flex items-center">
                                    <flux:icon.clock class="h-4 w-4 mr-2" />
                                    <span>Last seen: {{ $device->last_seen_at->diffForHumans() }}</span>
                                </div>
                            @endif

                            @if($device->capabilities)
                                <div class="flex items-center">
                                    <flux:icon.beaker class="h-4 w-4 mr-2" />
                                    <span>{{ count($device->capabilities['sensors'] ?? []) }} sensors, {{ count($device->capabilities['actuators'] ?? []) }} actuators</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-6">
                            <flux:button href="{{ route('devices.show', $device->public_id) }}" variant="ghost" class="w-full">
                                View Details
                            </flux:button>
                        </div>
                    </flux:card>
                @endforeach
            </div>

            <div class="mt-6 text-center">
                <flux:button href="{{ route('devices.pair') }}" variant="primary">
                    Pair Another Device
                </flux:button>
            </div>
        @endif
    </div>
</div>
