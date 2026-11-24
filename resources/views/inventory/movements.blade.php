@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('inventory.index') ? route('inventory.index') : url('/inventory');
    $movementsUrl = \Illuminate\Support\Facades\Route::has('inventory.movements') ? route('inventory.movements') : url('/inventory/movements');
    $opnamesUrl = \Illuminate\Support\Facades\Route::has('inventory.opnames') ? route('inventory.opnames') : url('/inventory/opnames');
@endphp

<x-app-layout title="Stock Movement Ledger">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Stock Movement Ledger
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Immutable chronological history of every stock IN, OUT, RESERVE, RELEASE, ADJUSTMENT, and RETURN event.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $indexUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    &larr; Stock Balances
                </a>
                <a href="{{ $opnamesUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#0F766E] bg-[#0F766E] text-white hover:bg-[#0D655E] transition-colors">
                    Stock Opnames
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
            <form method="GET" action="{{ $movementsUrl }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1">
                    <label for="search" class="block text-xs font-semibold text-[#142B4A] mb-1">Search Product / Reason</label>
                    <input id="search"
                           type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search SKU, product name, or movement reason..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                </div>

                <div class="w-full lg:w-48">
                    <label for="type" class="block text-xs font-semibold text-[#142B4A] mb-1">Movement Type</label>
                    <select id="type"
                            name="type"
                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">All Types</option>
                        @foreach ($movementTypes as $movementType)
                            <option value="{{ $movementType }}" @selected($type === $movementType)>{{ $movementType }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full lg:w-52">
                    <label for="warehouse_id" class="block text-xs font-semibold text-[#142B4A] mb-1">Warehouse</label>
                    <select id="warehouse_id"
                            name="warehouse_id"
                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">All Warehouses</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected($warehouseId === $wh->id)>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold transition-colors">
                        Filter
                    </button>
                    <a href="{{ $movementsUrl }}"
                       class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-slate-600 text-sm font-medium transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Timestamp</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Before &rarr; After</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Reference / Reason</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($movements as $movement)
                            @php
                                $badgeClass = match ($movement->type) {
                                    'IN', 'RETURN_IN' => 'bg-emerald-50 text-[#0F766E] border-emerald-200',
                                    'OUT', 'RETURN_OUT' => 'bg-red-50 text-[#DC2626] border-red-200',
                                    'RESERVE' => 'bg-amber-50 text-[#D97706] border-amber-200',
                                    'RELEASE' => 'bg-sky-50 text-[#2563EB] border-sky-200',
                                    default => 'bg-slate-100 text-[#142B4A] border-slate-300',
                                };
                            @endphp
                            <tr class="hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap font-mono">
                                    {{ $movement->created_at?->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#142B4A]">{{ $movement->product->name ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $movement->product->sku ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 text-xs">
                                    {{ $movement->warehouse->code ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                        {{ $movement->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[#142B4A]">
                                    {{ $movement->qty > 0 && $movement->type === 'ADJUSTMENT' ? '+' . number_format($movement->qty) : number_format($movement->qty) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-slate-600">
                                    {{ number_format($movement->balance_before) }} &rarr; <span class="font-bold text-[#142B4A]">{{ number_format($movement->balance_after) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-slate-800">{{ $movement->reason ?: '-' }}</div>
                                    @if ($movement->reference_type)
                                        <div class="text-[11px] text-slate-400 font-mono">
                                            {{ class_basename($movement->reference_type) }}{{ $movement->reference_id ? ' #' . $movement->reference_id : '' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ $movement->creator->name ?? 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                                    No stock movement entries recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="px-4 py-3 border-t border-[#DCE3EA] bg-[#F8FAFC]">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
