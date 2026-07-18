<x-app-layout title="Profile">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Profile') }}
            </h2>
            <x-secondary-button href="{{ route('dashboard') }}" wire:navigate>
                &larr; {{ __('Back to Dashboard') }}
            </x-secondary-button>
        </div>
    </x-slot>

    <livewire:profile.edit-profile />

    <livewire:profile.update-password />
</x-app-layout>
