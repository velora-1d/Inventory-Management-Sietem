<x-app-layout title="Pengaturan">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Pengaturan Sistem
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Kelola identitas toko, payment gateway, rekening transfer, dan format mata uang.</p>
            </div>
        </div>
    </x-slot>

    <livewire:settings.settings-panel />
</x-app-layout>
