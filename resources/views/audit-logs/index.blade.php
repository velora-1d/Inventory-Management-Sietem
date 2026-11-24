<x-app-layout title="Audit Log">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-foreground leading-tight">
                    {{ __('Audit Log') }}
                </h2>
                <p class="text-xs text-muted-foreground mt-0.5">Jejak perubahan data sensitif dan aktivitas sistem.</p>
            </div>
        </div>
    </x-slot>

    <livewire:audit-logs.audit-log-table />
</x-app-layout>
