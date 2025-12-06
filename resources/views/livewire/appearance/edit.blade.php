<?php

use function Livewire\Volt\{state};

?>

<x-settings.layout>
    <x-slot name="heading">{{ __('Appearance') }}</x-slot>
    <x-slot name="subheading">{{ __('Customize the look and feel of your dashboard') }}</x-slot>

    <div class="max-w-7xl space-y-6">
        <livewire:settings.appearance />
    </div>
</x-settings.layout>