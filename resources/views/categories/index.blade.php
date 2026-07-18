<x-app-layout title="Categories">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Categories') }}
            </h2>
            <x-primary-button x-data x-on:click="$dispatch('create-category')">
                <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                {{ __('Create Category') }}
            </x-primary-button>
        </div>
    </x-slot>

    <livewire:categories.category-table />

    <livewire:categories.category-form />
    <livewire:categories.category-detail />
</x-app-layout>
