<x-app-layout title="Settings">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-foreground leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <livewire:settings.setting-table />

    <livewire:settings.setting-form />
</x-app-layout>
