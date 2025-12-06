<div class="space-y-4">
    <div class="flex items-center gap-2">
        <button wire:click="goToPrevious" class="px-2 py-1 border rounded">&lt;</button>
        <button wire:click="goToToday" class="px-2 py-1 border rounded">Heute</button>
        <button wire:click="goToNext" class="px-2 py-1 border rounded">&gt;</button>
        <span
            class="ml-4 font-semibold">{{ \Illuminate\Support\Carbon::parse($currentDate)->isoFormat('MMMM YYYY') }}</span>

        <div class="ml-auto flex items-center gap-2">
            <select wire:change="setViewMode($event.target.value)" class="border rounded px-2 py-1">
                <option value="month" @selected($viewMode === 'month')>Monat</option>
                <option value="week" @selected($viewMode === 'week')>Woche</option>
                <option value="day" @selected($viewMode === 'day')>Tag</option>
            </select>
        </div>
    </div>

    {{-- Simple month grid skeleton --}}
    @php
        $start = \Illuminate\Support\Carbon::parse($currentDate)->startOfMonth()->startOfWeek();
        $end = \Illuminate\Support\Carbon::parse($currentDate)->endOfMonth()->endOfWeek();
        // Guard against null start_at values to prevent 500 errors
        $eventsByDate = collect($events)
            ->filter(fn($e) => !empty($e['start_at']))
            ->groupBy(fn($e) => \Illuminate\Support\Carbon::parse($e['start_at'])->toDateString());

        // Build weeks array: each week contains 7 Carbon dates
        $weeks = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $cursor->copy();
                $cursor->addDay();
            }
            $weeks[] = $week;
        }
        $weekdayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    @endphp

    <div class="max-w-[1200px] mx-auto grid grid-cols-12 gap-4">
        <!-- Sidebar -->
        <aside class="col-span-3 border rounded p-3 bg-white dark:bg-zinc-900">
            <div class="space-y-3">
                <div>
                    <div class="text-sm font-semibold mb-1">Filter</div>
                    <label class="block text-xs mb-1">Kalender</label>
                    <select class="w-full border rounded px-2 py-1" wire:model="selectedCalendarId">
                        <option value="">Alle</option>
                        @foreach($calendars as $cal)
                            <option value="{{ $cal['id'] }}">{{ $cal['name'] }}</option>
                        @endforeach
                    </select>
                    <label class="block text-xs mt-2 mb-1">Device</label>
                    <select class="w-full border rounded px-2 py-1" wire:model="selectedDeviceId">
                        <option value="">Alle</option>
                        @foreach($devices as $dev)
                            <option value="{{ $dev['id'] }}">{{ $dev['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="text-sm font-semibold mb-1">Legende</div>
                    <div class="text-xs text-neutral-600 dark:text-neutral-300 space-y-1">
                        <div class="flex items-center gap-2"><span
                                class="h-3 w-3 rounded bg-green-500 inline-block"></span> Aktiv</div>
                        <div class="flex items-center gap-2"><span
                                class="h-3 w-3 rounded bg-blue-500 inline-block"></span> Geplant</div>
                        <div class="flex items-center gap-2"><span
                                class="h-3 w-3 rounded bg-gray-500 inline-block"></span> Erledigt</div>
                        <div class="flex items-center gap-2"><span
                                class="h-3 w-3 rounded bg-red-500 inline-block"></span> Gecancelt</div>
                    </div>
                </div>

                <div>
                    <div class="text-sm font-semibold mb-1">Hinweise</div>
                    <ul class="text-xs list-disc ms-4 text-neutral-600 dark:text-neutral-300">
                        <li>Klick auf leeren Tag: neues Event</li>
                        <li>Klick auf Badge: Event bearbeiten</li>
                        <li>Drag&Drop/Resize folgt</li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Calendar Grid -->
        <div class="col-span-9 space-y-2">
            <!-- Weekday header -->
            <div class="grid grid-cols-7 gap-3" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                @foreach ($weekdayNames as $wd)
                    <div class="text-xs text-neutral-600 dark:text-neutral-300 font-semibold px-2">{{ $wd }}</div>
                @endforeach
            </div>

            <div wire:loading.class="opacity-50" wire:target="loadEventsForRange,selectedCalendarId,selectedDeviceId">
                <!-- Weeks rows -->
                @foreach ($weeks as $week)
                    <div class="grid grid-cols-7 gap-3" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                        @foreach ($week as $day)
                            @php $dayStr = $day->toDateString(); @endphp
                            <div class="p-2 min-h-[110px] bg-white dark:bg-zinc-900 border border-neutral-200 dark:border-zinc-700 hover:bg-neutral-50 dark:hover:bg-zinc-800"
                                wire:click="createEventFromDay('{{ $dayStr }}')">
                                <div
                                    class="text-[11px] text-neutral-700 dark:text-neutral-200 flex items-center justify-between">
                                    <span class="font-medium">{{ $day->isoFormat('DD.MM.') }}</span>
                                    <span class="text-[10px] opacity-70">{{ $day->isoFormat('dd') }}</span>
                                </div>
                                <div class="mt-1 space-y-1">
                                    @foreach ($eventsByDate->get($dayStr, []) as $evt)
                                        @php
                                            $statusColors = [
                                                'scheduled' => 'bg-blue-500 dark:bg-blue-600',
                                                'completed' => 'bg-gray-500 dark:bg-gray-600',
                                                'canceled' => 'bg-red-500 dark:bg-red-600',
                                                'active' => 'bg-green-500 dark:bg-green-600',
                                            ];
                                            $bgColor = $statusColors[$evt['status'] ?? 'scheduled'] ?? 'bg-neutral-200 dark:bg-neutral-800';
                                            $textColor = in_array($evt['status'] ?? '', ['scheduled', 'completed', 'canceled', 'active'])
                                                ? 'text-white'
                                                : 'text-neutral-900 dark:text-neutral-100';
                                        @endphp
                                        <div class="text-[11px] px-2 py-0.5 truncate rounded {{ $bgColor }} {{ $textColor }} cursor-pointer"
                                            wire:click.stop="openEvent({{ $evt['id'] }})" title="{{ $evt['title'] }}">
                                            {{ \Illuminate\Support\Str::limit($evt['title'], 24) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @php($hasEventForm = class_exists(\App\Livewire\EventForm::class))
    @if($hasEventForm)
        @livewire('event-form')
    @endif
</div>