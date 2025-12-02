@props(['wire:model.live' => null])

<div {{ $attributes->merge(['class' => 'border-b border-gray-200 dark:border-gray-700']) }}>
    <nav class="flex space-x-4" aria-label="Tabs">
        {{ $slot }}
    </nav>
</div>
