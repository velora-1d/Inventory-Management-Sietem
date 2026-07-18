<x-app-layout title="Purchases">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Purchases') }}
            </h2>
            <x-primary-button
                x-data
                x-on:click="window.location.href = '{{ route('purchases.create') }}'"
            >
                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                {{ __('Create Purchase') }}
            </x-primary-button>
        </div>
    </x-slot>

    <livewire:purchases.purchase-table />

    <livewire:components.delete-modal />
</x-app-layout>
