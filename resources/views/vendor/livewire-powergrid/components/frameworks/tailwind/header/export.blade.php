<div
    x-data="{ countChecked: @entangle('checkboxValues').live }"
    class="flex items-center gap-2"
>
    <!-- XLSX Button Group -->
    @if (in_array('xlsx', data_get($setUp, 'exportable.type')))
        <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white shrink-0">
            <!-- XLSX Export All / Filtered -->
            <button
                wire:click.prevent="exportToXLS"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-sky-700 bg-sky-50/50 hover:bg-sky-50 border-r border-slate-100 transition-colors focus:outline-none"
                title="Export all records to Excel"
            >
                <x-livewire-powergrid::icons.download class="h-3.5 w-3.5 text-sky-600" />
                <span>XLSX ({{ $this->total }})</span>
            </button>

            <!-- XLSX Export Selected -->
            @if ($checkbox)
                <button 
                    wire:click.prevent="exportToXLS(true)"
                    x-bind:disabled="countChecked.length === 0"
                    :class="countChecked.length === 0 ? 'opacity-40 cursor-not-allowed bg-white text-slate-400' : 'bg-white text-sky-700 hover:bg-sky-50'"
                    class="px-2.5 py-2 text-[11px] font-bold transition-colors focus:outline-none"
                    title="Export selected rows to Excel"
                >
                    <span x-text="`(${countChecked.length}) Selected`"></span>
                </button>
            @endif
        </div>
    @endif

    <!-- CSV Button Group -->
    @if (in_array('csv', data_get($setUp, 'exportable.type')))
        <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white shrink-0">
            <!-- CSV Export All / Filtered -->
            <button
                wire:click.prevent="exportToCsv"
                class="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-indigo-700 bg-indigo-50/50 hover:bg-indigo-50 border-r border-slate-100 transition-colors focus:outline-none"
                title="Export all records to CSV"
            >
                <x-livewire-powergrid::icons.download class="h-3.5 w-3.5 text-indigo-600" />
                <span>CSV ({{ $this->total }})</span>
            </button>

            <!-- CSV Export Selected -->
            @if ($checkbox)
                <button
                    wire:click.prevent="exportToCsv(true)"
                    x-bind:disabled="countChecked.length === 0"
                    :class="countChecked.length === 0 ? 'opacity-40 cursor-not-allowed bg-white text-slate-400' : 'bg-white text-indigo-700 hover:bg-indigo-50'"
                    class="px-2.5 py-2 text-[11px] font-bold transition-colors focus:outline-none"
                    title="Export selected rows to CSV"
                >
                    <span x-text="`(${countChecked.length}) Selected`"></span>
                </button>
            @endif
        </div>
    @endif
</div>
