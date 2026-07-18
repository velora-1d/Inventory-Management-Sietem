<x-app-layout title="Units">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Units') }}
            </h2>
            <x-primary-button x-data x-on:click="$dispatch('create-unit')">
                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                {{ __('Create Unit') }}
            </x-primary-button>
        </div>
    </x-slot>

    <livewire:units.unit-table />

    <livewire:units.unit-form />
    <livewire:units.unit-detail />
</x-app-layout>
