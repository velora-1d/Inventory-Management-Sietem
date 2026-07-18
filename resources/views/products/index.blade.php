<x-app-layout title="Products">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Products') }}
            </h2>
            <x-primary-button x-data x-on:click="$dispatch('create-product')">
                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                {{ __('Create Product') }}
            </x-primary-button>
        </div>
    </x-slot>

    <livewire:products.product-table />

    <livewire:products.product-form />
    <livewire:products.product-detail />
</x-app-layout>
