<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Skriptspeicher (C++ Snippets)</h2>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            + Script hinzufügen
        </button>
    </div>
    @if(session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded">
            {{ session('message') }}
        </div>
    @endif
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach($scripts as $script)
            <div class="border border-neutral-200 dark:border-neutral-700 rounded-lg p-4 bg-white dark:bg-neutral-800">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-semibold text-lg">{{ $script->name }}</h3>
                    <span class="px-2 py-1 text-xs rounded {{ $script->status === 'compiled' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($script->status === 'error' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400') }}">
                        {{ ucfirst($script->status) }}
                    </span>
                </div>
                <div class="text-sm space-y-1 mb-3">
                    <p class="text-neutral-500 dark:text-neutral-400">
                        <span class="font-medium">Device:</span> {{ $script->device ? $script->device->name : 'Kein Device' }}
                    </p>
                    <p class="text-neutral-500 dark:text-neutral-400">
                        <span class="font-medium">Sprache:</span> {{ strtoupper($script->language) }}
                    </p>
                    <p class="text-neutral-500 dark:text-neutral-400 truncate">
                        <span class="font-medium">Beschreibung:</span> {{ Str::limit($script->description, 40) }}
                    </p>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex gap-2">
                        <button wire:click="editScript({{ $script->id }})" class="flex-1 px-3 py-1 text-sm border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">✏️ Bearbeiten</button>
                        <button wire:click="deleteScript({{ $script->id }})" onclick="return confirm('Wirklich löschen?')" class="px-3 py-1 text-sm border border-red-500 text-red-500 rounded hover:bg-red-50 dark:hover:bg-red-900/30">🗑️</button>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            onclick="compileScript({{ $script->id }})"
                            class="flex-1 px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                            🔨 Kompilieren
                        </button>
                        <button 
                            onclick="uploadScript({{ $script->id }})"
                            class="flex-1 px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                            {{ $script->status !== 'compiled' && $script->status !== 'flashed' ? 'disabled' : '' }}>
                            📤 Flashen
                        </button>
                    </div>
                </div>
                @if($script->compile_log)
                    <div class="mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs text-gray-700 dark:text-gray-300 max-h-24 overflow-auto">
                        <span class="font-bold">Compile Log:</span><br>{{ $script->compile_log }}
                    </div>
                @endif
                @if($script->flash_log)
                    <div class="mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs text-gray-700 dark:text-gray-300 max-h-24 overflow-auto">
                        <span class="font-bold">Flash Log:</span><br>{{ $script->flash_log }}
                    </div>
                @endif
            </div>
        @endforeach
        @if($scripts->isEmpty())
            <div class="col-span-full text-center py-12 text-neutral-500 dark:text-neutral-400">
                Keine Skripte hinterlegt. Füge dein erstes C++ Snippet hinzu!
            </div>
        @endif
    </div>
    <!-- Create Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Neues Script</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Beschreibung</label>
                        <input type="text" wire:model="description" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                        <label class="block text-sm font-medium mb-1">C++ Code</label>
                        <textarea wire:model="code" rows="8" class="w-full px-3 py-2 border rounded font-mono dark:bg-neutral-700 dark:border-neutral-600"></textarea>
                        @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button wire:click="createScript" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Erstellen</button>
                    <button wire:click="$set('showCreateModal', false)" class="flex-1 px-4 py-2 border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">Abbrechen</button>
                </div>
            </div>
        </div>
    @endif
    <!-- Edit Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Script bearbeiten</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Beschreibung</label>
                        <input type="text" wire:model="description" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                        <label class="block text-sm font-medium mb-1">C++ Code</label>
                        <textarea wire:model="code" rows="8" class="w-full px-3 py-2 border rounded font-mono dark:bg-neutral-700 dark:border-neutral-600"></textarea>
                        @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button wire:click="updateScript" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Speichern</button>
                    <button wire:click="$set('showEditModal', false)" class="flex-1 px-4 py-2 border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">Abbrechen</button>
                </div>
            </div>
        </div>
    @endif

    <script>
        async function compileScript(scriptId) {
            // First, get available devices
            const devicesResponse = await fetch('/api/arduino/devices', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const devicesData = await devicesResponse.json();
            
            if (!devicesData.devices || devicesData.devices.length === 0) {
                alert('❌ Keine Online-Devices gefunden! Stelle sicher, dass mindestens ein Device-Agent läuft.');
                return;
            }

            // Show device selection
            let deviceOptions = devicesData.devices.map(d => `${d.name} (${d.bootstrap_id})`).join('\n');
            const deviceId = prompt(`Wähle Device für Kompilierung:\n\n${deviceOptions}\n\nGib Device-ID ein:`, devicesData.devices[0].id);
            if (!deviceId) return;

            const board = prompt('Board FQBN (z.B. esp32:esp32:esp32):', 'esp32:esp32:esp32');
            if (!board) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ Sende Befehl...';

            try {
                const response = await fetch(`/api/arduino/scripts/${scriptId}/compile`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ device_id: deviceId, board })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Compile-Befehl gesendet an: ${data.device}\n\nDer Agent kompiliert das Script jetzt. Logs erscheinen hier nach Abschluss.`);
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    alert('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                alert('❌ Netzwerkfehler: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.textContent = '🔨 Kompilieren';
            }
        }

        async function uploadScript(scriptId) {
            // Get available devices
            const devicesResponse = await fetch('/api/arduino/devices', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const devicesData = await devicesResponse.json();
            
            if (!devicesData.devices || devicesData.devices.length === 0) {
                alert('❌ Keine Online-Devices gefunden!');
                return;
            }

            let deviceOptions = devicesData.devices.map(d => `${d.name} (${d.bootstrap_id})`).join('\n');
            const deviceId = prompt(`Wähle Device mit Arduino CLI:\n\n${deviceOptions}\n\nGib Device-ID ein:`, devicesData.devices[0].id);
            if (!deviceId) return;

            const port = prompt('Serieller Port am Agent (z.B. COM3, /dev/ttyUSB0):', 'COM3');
            if (!port) return;

            const board = prompt('Board FQBN:', 'esp32:esp32:esp32');
            if (!board) return;

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ Sende Befehl...';

            try {
                const response = await fetch(`/api/arduino/scripts/${scriptId}/upload`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ device_id: deviceId, port, board })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ Upload-Befehl gesendet an: ${data.device}\n\nDer Agent flasht das Target-Device jetzt.`);
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    alert('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                alert('❌ Netzwerkfehler: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.textContent = '📤 Flashen';
            }
        }
    </script>
</div>
