<div x-data="{ open: $wire.entangle('open') }" x-show="open" class="fixed inset-0 bg-black/30 flex items-center justify-center">
    <div class="bg-white rounded shadow p-4 w-[420px]">
        <div class="font-semibold mb-2">Event</div>
        <div class="space-y-2">
            <input type="text" wire:model="title" class="w-full border rounded px-2 py-1" placeholder="Titel" />
            <textarea wire:model="description" class="w-full border rounded px-2 py-1" placeholder="Beschreibung"></textarea>
            <div class="flex gap-2">
                <input type="datetime-local" wire:model="start_at" class="border rounded px-2 py-1 w-1/2" />
                <input type="datetime-local" wire:model="end_at" class="border rounded px-2 py-1 w-1/2" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="all_day" /> Ganztägig
            </label>
            <div class="flex gap-2">
                <input type="text" wire:model="color" class="border rounded px-2 py-1 w-1/2" placeholder="#color" />
                <select wire:model="status" class="border rounded px-2 py-1 w-1/2">
                    <option value="planned">planned</option>
                    <option value="active">active</option>
                    <option value="done">done</option>
                    <option value="canceled">canceled</option>
                </select>
            </div>
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
