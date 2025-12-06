<div x-data="{ open: $wire.entangle('open') }" x-show="open"
    class="fixed inset-0 bg-black/30 flex items-center justify-center">
    <div class="bg-white rounded shadow p-4 w-[460px]">
        <div class="font-semibold mb-2">Event</div>
        <div class="space-y-2">
            <input type="text" wire:model="title" class="w-full border rounded px-2 py-1" placeholder="Titel" />
            <textarea wire:model="description" class="w-full border rounded px-2 py-1"
                placeholder="Beschreibung"></textarea>
            <div class="flex gap-2">
                <input type="datetime-local" wire:model="start_at" class="border rounded px-2 py-1 w-1/2" />
                <input type="datetime-local" wire:model="end_at" class="border rounded px-2 py-1 w-1/2" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="all_day" /> Ganztägig
            </label>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs">Shelly Gerät</label>
                    <select wire:model="shelly_device_id" class="w-full border rounded px-2 py-1">
                        <option value="">Keins</option>
                        @foreach($shellyDevices as $sh)
                            <option value="{{ $sh['id'] }}">{{ $sh['name'] ?? ('Shelly #' . $sh['id']) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs">Shelly Aktion</label>
                    <select wire:model="shelly_action" class="w-full border rounded px-2 py-1">
                        <option value="">Keine</option>
                        <option value="on">An</option>
                        <option value="off">Aus</option>
                        <option value="toggle">Toggle</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs">Auto-Off Dauer (Minuten)</label>
                    <input type="number" min="1" max="1440" wire:model="duration_minutes"
                        class="w-full border rounded px-2 py-1" placeholder="z.B. 4" />
                </div>
                <div>
                    <label class="text-xs">Wiederholung (RRULE)</label>
                    <select wire:model="rrule" class="w-full border rounded px-2 py-1">
                        <option value="">Keine</option>
                        <option value="FREQ=DAILY;INTERVAL=1">Täglich</option>
                        <option value="FREQ=DAILY;INTERVAL=2">Alle 2 Tage</option>
                        <option value="FREQ=WEEKLY;INTERVAL=1">Wöchentlich</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <input type="text" wire:model="color" class="border rounded px-2 py-1 w-1/2" placeholder="#color" />
                <select wire:model="status" class="border rounded px-2 py-1 w-1/2">
                    <option value="scheduled">scheduled</option>
                    <option value="completed">completed</option>
                    <option value="canceled">canceled</option>
                </select>
            </div>

            @if($id && $shelly_device_id)
                <div class="mt-3 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs space-y-1">
                    <div class="font-semibold">Shelly Info</div>
                    <div>Gerät: <span class="font-mono">{{ $shelly_device_id }}</span></div>
                    <div>Aktion: <span class="font-mono">{{ $shelly_action ?? 'Keine' }}</span></div>
                    @if($duration_minutes)
                        <div>Auto-Off: <span class="font-mono">{{ $duration_minutes }} min</span></div>
                    @endif
                    @if($last_executed_at)
                        <div>Zuletzt ausgeführt: <span class="font-mono">{{ \Carbon\Carbon::parse($last_executed_at)->format('d.m.Y H:i') }}</span></div>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <button class="px-3 py-1 border rounded" @click="$wire.close()">Abbrechen</button>
            @if($id)
                <button class="px-3 py-1 border rounded" wire:click="delete">Löschen</button>
            @endif
            <button class="px-3 py-1 bg-blue-600 text-black border rounded" wire:click="save">Speichern</button>
        </div>
    </div>
</div>