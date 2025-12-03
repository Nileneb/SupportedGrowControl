<x-layouts.app.sidebar :title="$title ?? null">
    @livewire('exit-remote-support')
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
