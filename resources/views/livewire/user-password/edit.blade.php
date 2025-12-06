<?php

use function Livewire\Volt\{state};

?>

<x-settings.layout>
    <x-slot name="heading">{{ __('Password') }}</x-slot>
    <x-slot name="subheading">{{ __('Update your account password') }}</x-slot>

    <div class="max-w-7xl space-y-6">
        <livewire:settings.password />
    </div>
</x-settings.layout>