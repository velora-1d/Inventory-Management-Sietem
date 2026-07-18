<x-app-layout title="Suppliers">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Suppliers') }}
            </h2>
            <x-primary-button x-data x-on:click="$dispatch('create-supplier')">
                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                {{ __('Create Supplier') }}
            </x-primary-button>
        </div>
    </x-slot>

    <livewire:suppliers.supplier-table />

    <livewire:suppliers.supplier-form />
    <livewire:suppliers.supplier-detail />
</x-app-layout>
