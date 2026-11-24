@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('inventory.index') ? route('inventory.index') : url('/inventory');
    $movementsUrl = \Illuminate\Support\Facades\Route::has('inventory.movements') ? route('inventory.movements') : url('/inventory/movements');
    $opnamesUrl = \Illuminate\Support\Facades\Route::has('inventory.opnames') ? route('inventory.opnames') : url('/inventory/opnames');
    $adjustUrl = \Illuminate\Support\Facades\Route::has('inventory.adjust') ? route('inventory.adjust') : url('/inventory/adjust');
@endphp

<x-app-layout title="Inventory Ledger">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Inventory Stock Ledger
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Real-time warehouse stock balances: on-hand, reserved, available, and incoming quantities.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $movementsUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    Movement Ledger
                </a>
                <a href="{{ $opnamesUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#0F766E] bg-[#0F766E] text-white hover:bg-[#0D655E] transition-colors">
                    Stock Opnames
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ adjustModalOpen: {{ $errors->any() ? 'true' : 'false' }}, selectedProductId: '{{ old('product_id', '') }}' }">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-[#0F766E] font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#DC2626] font-medium">
                {{ session('error') }}
            </div>
        @endif

        {{-- Summary KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tracked SKUs</p>
                <p class="text-2xl font-bold text-[#142B4A] mt-1">{{ number_format($summary['total_skus']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">On Hand</p>
                <p class="text-2xl font-bold text-[#142B4A] mt-1">{{ number_format($summary['total_on_hand']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reserved</p>
                <p class="text-2xl font-bold text-[#D97706] mt-1">{{ number_format($summary['total_reserved']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Available</p>
                <p class="text-2xl font-bold text-[#10B981] mt-1">{{ number_format($summary['total_available']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Incoming</p>
                <p class="text-2xl font-bold text-[#2563EB] mt-1">{{ number_format($summary['total_incoming']) }}</p>
            </div>
        </div>

        {{-- Filters & Quick Adjustment Trigger --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
            <form method="GET" action="{{ $indexUrl }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1">
                    <label for="search" class="block text-xs font-semibold text-[#142B4A] mb-1">Search SKU / Product</label>
                    <input id="search"
                           type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search by product name or SKU..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                </div>

                <div class="w-full lg:w-52">
                    <label for="warehouse_id" class="block text-xs font-semibold text-[#142B4A] mb-1">Warehouse</label>
                    <select id="warehouse_id"
                            name="warehouse_id"
                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected($selectedWarehouseId === $wh->id)>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full lg:w-44">
                    <label for="stock_status" class="block text-xs font-semibold text-[#142B4A] mb-1">Stock Status</label>
                    <select id="stock_status"
                            name="stock_status"
                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">All Items</option>
                        <option value="low" @selected($stockStatus === 'low')>Low Stock</option>
                        <option value="out" @selected($stockStatus === 'out')>Out of Stock</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold transition-colors">
                        Filter
                    </button>
                    <a href="{{ $indexUrl }}"
                       class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-slate-600 text-sm font-medium transition-colors">
                        Reset
                    </a>
                    <button type="button"
                            @click="selectedProductId = ''; adjustModalOpen = true"
                            class="px-4 py-2 rounded-lg bg-[#142B4A] hover:bg-[#0F223B] text-white text-sm font-semibold transition-colors">
                        + Quick Adjust
                    </button>
                </div>
            </form>
        </div>

        {{-- Balances Table --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">On Hand</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Reserved</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Available</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Incoming</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($balances as $balance)
                            @php
                                $product = $balance->product;
                                $available = $balance->available;
                                $minStock = (int) ($product->min_stock ?? 0);
                            @endphp
                            <tr class="hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#142B4A]">{{ $product->name ?? 'Unknown Product' }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $product->sku ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $product?->category?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-[#142B4A]">
                                    {{ number_format($balance->on_hand) }}
                                    <span class="text-xs font-normal text-slate-400">{{ $product?->unit?->symbol ?? $product?->unit?->name ?? '' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-[#D97706]">
                                    {{ number_format($balance->reserved) }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold {{ $available <= 0 ? 'text-[#DC2626]' : 'text-[#10B981]' }}">
                                    {{ number_format($available) }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-[#2563EB]">
                                    {{ number_format($balance->incoming) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($available <= 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-[#DC2626] border border-red-200">
                                            Out of Stock
                                        </span>
                                    @elseif ($available <= $minStock)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-[#D97706] border border-amber-200">
                                            Low Stock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-[#0F766E] border border-emerald-200">
                                            Healthy
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button"
                                            @click="selectedProductId = '{{ $balance->product_id }}'; adjustModalOpen = true"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-xs font-semibold text-[#2563EB] transition-colors">
                                        Adjust
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                                    No stock balance records found for the selected filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($balances->hasPages())
                <div class="px-4 py-3 border-t border-[#DCE3EA] bg-[#F8FAFC]">
                    {{ $balances->links() }}
                </div>
            @endif
        </div>

        {{-- Stock Adjustment Modal --}}
        <div x-show="adjustModalOpen"
             x-cloak
             style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="adjustModalOpen = false"
                 class="w-full max-w-md bg-white border border-[#DCE3EA] rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 bg-[#142B4A] text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Quick Stock Adjustment</h3>
                        <p class="text-xs text-sky-200">Every adjustment creates an immutable movement &amp; audit entry.</p>
                    </div>
                    <button type="button" @click="adjustModalOpen = false" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <form method="POST" action="{{ $adjustUrl }}" class="p-5 space-y-4">
                    @csrf
                    <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}" />

                    <div>
                        <label for="adjust_product_id" class="block text-xs font-semibold text-[#142B4A] mb-1">Product</label>
                        <select id="adjust_product_id"
                                name="product_id"
                                x-model="selectedProductId"
                                required
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                            <option value="">-- Select Product --</option>
                            @foreach ($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->sku }} — {{ $prod->name }} (Qty: {{ $prod->quantity }})</option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <p class="text-xs text-[#DC2626] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="delta_qty" class="block text-xs font-semibold text-[#142B4A] mb-1">Delta Quantity (+/-)</label>
                        <input id="delta_qty"
                               type="number"
                               name="delta_qty"
                               value="{{ old('delta_qty') }}"
                               placeholder="e.g. 10 or -3"
                               required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                        <p class="text-[11px] text-slate-500 mt-1">Positive value increases stock; negative value decreases available stock.</p>
                        @error('delta_qty')
                            <p class="text-xs text-[#DC2626] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reason" class="block text-xs font-semibold text-[#142B4A] mb-1">Reason (Mandatory)</label>
                        <input id="reason"
                               type="text"
                               name="reason"
                               value="{{ old('reason') }}"
                               placeholder="e.g. Damaged item write-off or physical recount"
                               minlength="3"
                               required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                        @error('reason')
                            <p class="text-xs text-[#DC2626] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#DCE3EA]">
                        <button type="button"
                                @click="adjustModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600 hover:bg-[#F8FAFC]">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">
                            Save Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
