<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Webcam Verwaltung</h2>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            + Webcam hinzufügen
        </button>
    </div>

    @if(session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach($webcams as $webcam)
            <div class="border border-neutral-200 dark:border-neutral-700 rounded-lg p-4 bg-white dark:bg-neutral-800">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-semibold text-lg">{{ $webcam->name }}</h3>
                    <span class="px-2 py-1 text-xs rounded {{ $webcam->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400' }}">
                        {{ $webcam->is_active ? 'Aktiv' : 'Inaktiv' }}
                    </span>
                </div>

                <div class="text-sm space-y-1 mb-3">
                    <p class="text-neutral-500 dark:text-neutral-400">
                        <span class="font-medium">Typ:</span> {{ strtoupper($webcam->type) }}
                    </p>
                    @if($webcam->device)
                        <p class="text-neutral-500 dark:text-neutral-400">
                            <span class="font-medium">Device:</span> {{ $webcam->device->name }}
                        </p>
                    @endif
                    <p class="text-neutral-500 dark:text-neutral-400 truncate">
                        <span class="font-medium">URL:</span> {{ Str::limit($webcam->stream_url, 30) }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button wire:click="editWebcam({{ $webcam->id }})" class="flex-1 px-3 py-1 text-sm border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">
                        ✏️ Bearbeiten
                    </button>
                    <button wire:click="toggleActive({{ $webcam->id }})" class="px-3 py-1 text-sm border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">
                        {{ $webcam->is_active ? '⏸️' : '▶️' }}
                    </button>
                    <button wire:click="deleteWebcam({{ $webcam->id }})" onclick="return confirm('Wirklich löschen?')" class="px-3 py-1 text-sm border border-red-500 text-red-500 rounded hover:bg-red-50 dark:hover:bg-red-900/30">
                        🗑️
                    </button>
                </div>
            </div>
        @endforeach

        @if($webcams->isEmpty())
            <div class="col-span-full text-center py-12 text-neutral-500 dark:text-neutral-400">
                Keine Webcams konfiguriert. Füge deine erste Webcam hinzu!
            </div>
        @endif
    </div>

    <!-- Create Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Neue Webcam</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Stream URL</label>
                        <input type="url" wire:model="stream_url" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('stream_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Snapshot URL (optional)</label>
                        <input type="url" wire:model="snapshot_url" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('snapshot_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Typ</label>
                        <select wire:model="type" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                            <option value="mjpeg">MJPEG</option>
                            <option value="hls">HLS</option>
                            <option value="webrtc">WebRTC</option>
                            <option value="image">Einzelbild</option>
                        </select>
                        @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Device (optional)</label>
                        <select wire:model="device_id" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                            <option value="">Kein Device</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                        @error('device_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Aktualisierungsintervall (ms, nur für Einzelbild)</label>
                        <input type="number" wire:model="refresh_interval" min="100" max="10000" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('refresh_interval') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" id="create_is_active" class="rounded">
                        <label for="create_is_active" class="text-sm font-medium">Aktiv</label>
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button wire:click="createWebcam" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Erstellen
                    </button>
                    <button wire:click="$set('showCreateModal', false)" class="flex-1 px-4 py-2 border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Webcam bearbeiten</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Stream URL</label>
                        <input type="url" wire:model="stream_url" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('stream_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Snapshot URL (optional)</label>
                        <input type="url" wire:model="snapshot_url" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('snapshot_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Typ</label>
                        <select wire:model="type" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                            <option value="mjpeg">MJPEG</option>
                            <option value="hls">HLS</option>
                            <option value="webrtc">WebRTC</option>
                            <option value="image">Einzelbild</option>
                        </select>
                        @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Device (optional)</label>
                        <select wire:model="device_id" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                            <option value="">Kein Device</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }}</option>
                            @endforeach
                        </select>
                        @error('device_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Aktualisierungsintervall (ms)</label>
                        <input type="number" wire:model="refresh_interval" min="100" max="10000" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('refresh_interval') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" id="edit_is_active" class="rounded">
                        <label for="edit_is_active" class="text-sm font-medium">Aktiv</label>
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button wire:click="updateWebcam" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Speichern
                    </button>
                    <button wire:click="$set('showEditModal', false)" class="flex-1 px-4 py-2 border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
