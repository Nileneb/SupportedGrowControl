<?php

use function Livewire\Volt\{state};

?>

<x-settings.layout>
    <x-slot name="heading">{{ __('Profile') }}</x-slot>
    <x-slot name="subheading">{{ __('Update your profile information and email address') }}</x-slot>

    <div class="max-w-7xl space-y-6">
        <livewire:settings.profile />

        <livewire:settings.delete-user-form />
    </div>
</x-settings.layout>