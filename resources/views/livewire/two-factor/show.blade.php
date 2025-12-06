<?php

use function Livewire\Volt\{state};

?>

<x-settings.layout>
    <x-slot name="heading">{{ __('Two-Factor Authentication') }}</x-slot>
    <x-slot
        name="subheading">{{ __('Add additional security to your account using two-factor authentication') }}</x-slot>

    <div class="max-w-7xl space-y-6">
        <livewire:settings.two-factor />
    </div>
</x-settings.layout>