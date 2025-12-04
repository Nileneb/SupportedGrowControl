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
                            onclick="compileScript(event, {{ $script->id }})"
                            class="flex-1 px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                            🔨 Kompilieren
                        </button>
                        <button
                            onclick="uploadScript(event, {{ $script->id }})"
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

    <!-- Compile Modal -->
    <div id="compileModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">🔨 Script kompilieren</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Device mit Arduino CLI</label>
                    <select id="compileDeviceSelect" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        <option value="">Lade Devices...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Board Typ</label>
                    <select id="compileBoardSelect" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        <option value="esp32:esp32:esp32">ESP32 Dev Module</option>
                        <option value="esp32:esp32:esp32s3">ESP32-S3 Dev Module</option>
                        <option value="esp32:esp32:esp32c3">ESP32-C3 Dev Module</option>
                        <option value="esp8266:esp8266:nodemcuv2">NodeMCU 1.0 (ESP-12E)</option>
                        <option value="arduino:avr:uno">Arduino Uno</option>
                        <option value="arduino:avr:mega">Arduino Mega</option>
                        <option value="arduino:avr:nano">Arduino Nano</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button onclick="submitCompile()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Kompilieren</button>
                <button onclick="closeCompileModal()" class="flex-1 px-4 py-2 border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">📤 Script flashen</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Device mit Arduino CLI</label>
                    <select id="uploadDeviceSelect" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        <option value="">Lade Devices...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Serieller Port</label>
                    <select id="uploadPortSelect" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        <option value="">Zuerst Device wählen...</option>
                    </select>
                    <p class="text-xs text-neutral-500 mt-1">Der Agent erkennt automatisch angeschlossene Boards</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Board Typ</label>
                    <select id="uploadBoardSelect" class="w-full px-3 py-2 border rounded dark:bg-neutral-700 dark:border-neutral-600">
                        <option value="esp32:esp32:esp32">ESP32 Dev Module</option>
                        <option value="esp32:esp32:esp32s3">ESP32-S3 Dev Module</option>
                        <option value="esp32:esp32:esp32c3">ESP32-C3 Dev Module</option>
                        <option value="esp8266:esp8266:nodemcuv2">NodeMCU 1.0 (ESP-12E)</option>
                        <option value="arduino:avr:uno">Arduino Uno</option>
                        <option value="arduino:avr:mega">Arduino Mega</option>
                        <option value="arduino:avr:nano">Arduino Nano</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button onclick="submitUpload()" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Flashen</button>
                <button onclick="closeUploadModal()" class="flex-1 px-4 py-2 border rounded hover:bg-neutral-50 dark:hover:bg-neutral-700">Abbrechen</button>
            </div>
        </div>
    </div>

    <script>
        let currentScriptId = null;
        let availableDevices = [];

        async function compileScript(event, scriptId) {
            currentScriptId = scriptId;
            
            // Fetch available devices
            try {
                const response = await fetch('/api/arduino/devices', {
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
                });
                const data = await response.json();
                availableDevices = data.devices || [];

                if (availableDevices.length === 0) {
                    alert('❌ Keine Online-Devices gefunden! Stelle sicher, dass mindestens ein Device-Agent läuft.');
                    return;
                }

                // Populate device select
                const select = document.getElementById('compileDeviceSelect');
                select.innerHTML = availableDevices.map(d => 
                    `<option value="${d.id}">${d.name} (${d.bootstrap_id})</option>`
                ).join('');

                // Show modal
                document.getElementById('compileModal').classList.remove('hidden');
            } catch (error) {
                alert('❌ Fehler beim Laden der Devices: ' + error.message);
            }
        }

        async function uploadScript(event, scriptId) {
            currentScriptId = scriptId;
            
            // Fetch available devices
            try {
                const response = await fetch('/api/arduino/devices', {
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
                });
                const data = await response.json();
                availableDevices = data.devices || [];

                if (availableDevices.length === 0) {
                    alert('❌ Keine Online-Devices gefunden!');
                    return;
                }

                // Populate device select
                const select = document.getElementById('uploadDeviceSelect');
                select.innerHTML = availableDevices.map(d => 
                    `<option value="${d.id}">${d.name} (${d.bootstrap_id})</option>`
                ).join('');

                // Add change listener to load ports
                select.onchange = async () => {
                    const deviceId = select.value;
                    if (!deviceId) return;

                    // TODO: Fetch available ports from agent API
                    // For now, show common options
                    const portSelect = document.getElementById('uploadPortSelect');
                    portSelect.innerHTML = `
                        <option value="COM3">COM3 (Windows)</option>
                        <option value="COM4">COM4 (Windows)</option>
                        <option value="COM5">COM5 (Windows)</option>
                        <option value="/dev/ttyUSB0">/dev/ttyUSB0 (Linux)</option>
                        <option value="/dev/ttyUSB1">/dev/ttyUSB1 (Linux)</option>
                        <option value="/dev/ttyACM0">/dev/ttyACM0 (Linux)</option>
                        <option value="/dev/cu.usbserial-0001">/dev/cu.usbserial-0001 (Mac)</option>
                    `;
                };

                // Show modal
                document.getElementById('uploadModal').classList.remove('hidden');
            } catch (error) {
                alert('❌ Fehler beim Laden der Devices: ' + error.message);
            }
        }

        async function submitCompile() {
            const deviceId = document.getElementById('compileDeviceSelect').value;
            const board = document.getElementById('compileBoardSelect').value;

            if (!deviceId || !board) {
                alert('❌ Bitte alle Felder ausfüllen!');
                return;
            }

            try {
                const response = await fetch(`/api/arduino/scripts/${currentScriptId}/compile`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ device_id: deviceId, board })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ Compile-Befehl gesendet an: ${data.device}\n\nDer Agent kompiliert das Script jetzt.`);
                    closeCompileModal();
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    alert('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                alert('❌ Netzwerkfehler: ' + error.message);
            }
        }

        async function submitUpload() {
            const deviceId = document.getElementById('uploadDeviceSelect').value;
            const port = document.getElementById('uploadPortSelect').value;
            const board = document.getElementById('uploadBoardSelect').value;

            if (!deviceId || !port || !board) {
                alert('❌ Bitte alle Felder ausfüllen!');
                return;
            }

            try {
                const response = await fetch(`/api/arduino/scripts/${currentScriptId}/upload`, {
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
                    closeUploadModal();
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    alert('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                alert('❌ Netzwerkfehler: ' + error.message);
            }
        }

        function closeCompileModal() {
            document.getElementById('compileModal').classList.add('hidden');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
        }
    </script>
</div>
