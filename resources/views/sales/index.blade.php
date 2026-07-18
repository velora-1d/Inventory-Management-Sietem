<x-app-layout title="Sales">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Sales') }}
            </h2>
            <x-primary-button
                x-data
                x-on:click="window.location.href = '{{ route('sales.create') }}'"
            >
                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                {{ __('Create Sale') }}
            </x-primary-button>
        </div>
    </x-slot>

    <livewire:sales.sales-table />

    <livewire:components.delete-modal />
</x-app-layout>
