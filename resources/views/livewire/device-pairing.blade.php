<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Pair New Device</h2>

        {{-- Success/Error Messages --}}
        @if($successMessage)
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6" role="alert">
                <p class="font-medium">{{ $successMessage }}</p>
            </div>
        @endif

        @if($errorMessage)
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6" role="alert">
                <p class="font-medium">{{ $errorMessage }}</p>
            </div>
        @endif

        {{-- Pairing Form --}}
        <form wire:submit="pair" class="mb-8">
            <div class="mb-4">
                <label for="bootstrapCode" class="block text-sm font-medium text-gray-700 mb-2">
                    Bootstrap Code
                </label>
                <div class="flex gap-3">
                    <input
                        type="text"
                        id="bootstrapCode"
                        wire:model="bootstrapCode"
                        placeholder="ABC123"
                        maxlength="6"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase text-lg tracking-widest"
                    >
                    <button
                        type="submit"
                        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Pair Device
                    </button>
                </div>
                @error('bootstrapCode')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-sm text-gray-600">
                Enter the 6-character code displayed on your device to pair it with your account.
            </p>
        </form>

        {{-- Unclaimed Devices List --}}
        @if($unclaimedDevices->count() > 0)
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Waiting for Pairing ({{ $unclaimedDevices->count() }})</h3>
                <div class="space-y-3">
                    @foreach($unclaimedDevices as $device)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-800">{{ $device->name }}</p>
                                <p class="text-sm text-gray-600">Bootstrap ID: {{ Str::limit($device->bootstrap_id, 20) }}</p>
                                <p class="text-xs text-gray-500">Created {{ $device->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-mono font-bold text-blue-600">{{ $device->bootstrap_code }}</p>
                                <p class="text-xs text-gray-500">Code</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="border-t border-gray-200 pt-6">
                <p class="text-gray-600 text-center py-8">
                    No devices waiting for pairing. Start your device and it will appear here.
                </p>
            </div>
        @endif
    </div>
</div>
