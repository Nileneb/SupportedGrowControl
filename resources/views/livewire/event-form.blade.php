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
                    <label class="text-xs">Device</label>
                    <select wire:model.live="device_id" class="w-full border rounded px-2 py-1">
                        <option value="">Keins</option>
                        @foreach($shellyDevices as $sh)
                            <option value="{{ $sh['id'] }}">{{ $sh['name'] ?? ('Shelly #' . $sh['id']) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs">Aktion</label>
                    <select wire:model="command_type" class="w-full border rounded px-2 py-1">
                        <option value="">Keine</option>
                        <option value="spray_pump">Spray Pump</option>
                        <option value="fill_valve">Fill Valve</option>
                        <option value="pump">Pump</option>
                        <option value="valve">Valve</option>
                        <option value="light">Light</option>
                        <option value="fan">Fan</option>
                    </select>
                </div>
            </div>
            
            @if($command_type && !empty($paramTemplate))
                <div class="bg-gray-50 rounded p-3 space-y-2">
                    <div class="text-xs font-semibold text-gray-700">Command Parameters</div>
                    @foreach($paramTemplate as $param)
                        @if($param['type'] === 'hidden')
                            {{-- Hidden fields: set but don't display --}}
                            <input type="hidden" wire:model="command_params.{{ $param['name'] }}" value="{{ $param['default'] ?? '' }}" />
                        @else
                            <div>
                                <label class="text-xs">{{ $param['label'] ?? ucfirst($param['name']) }}</label>
                                @if($param['type'] === 'select')
                                    <select wire:model="command_params.{{ $param['name'] }}" class="w-full border rounded px-2 py-1">
                                        @foreach($param['options'] ?? [] as $opt)
                                            <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                                        @endforeach
                                    </select>
                                @elseif($param['type'] === 'number')
                                    <input 
                                        type="number" 
                                        wire:model="command_params.{{ $param['name'] }}" 
                                        class="w-full border rounded px-2 py-1"
                                        @if(isset($param['min'])) min="{{ $param['min'] }}" @endif
                                        @if(isset($param['max'])) max="{{ $param['max'] }}" @endif
                                        @if($param['required'] ?? false) required @endif
                                    />
                                @else
                                    <input 
                                        type="text" 
                                        wire:model="command_params.{{ $param['name'] }}" 
                                        class="w-full border rounded px-2 py-1"
                                        @if($param['required'] ?? false) required @endif
                                    />
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
            
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs">Dauer (Minuten)</label>
                    <input type="number" min="1" max="1440" wire:model="duration_minutes" class="w-full border rounded px-2 py-1" placeholder="z.B. 4" />
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
                    <option value="planned">planned</option>
                    <option value="active">active</option>
                    <option value="done">done</option>
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
                        <div>Zuletzt ausgeführt: <span
                                class="font-mono">{{ \Carbon\Carbon::parse($last_executed_at)->format('d.m.Y H:i') }}</span>
                        </div>
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