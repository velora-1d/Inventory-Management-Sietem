@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('procurement.index') ? route('procurement.index') : url('/procurement');
    $returnsUrl = \Illuminate\Support\Facades\Route::has('procurement.returns') ? route('procurement.returns') : url('/procurement/returns');
    $storeUrl = \Illuminate\Support\Facades\Route::has('procurement.returns.store') ? route('procurement.returns.store') : url('/procurement/returns');
    $ordersUrl = \Illuminate\Support\Facades\Route::has('procurement.orders') ? route('procurement.orders') : url('/procurement/orders');

    $poProductsMap = $purchaseOrders->mapWithKeys(function ($po) {
        return [
            $po->id => $po->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Product',
                'unit_price' => (float) $item->unit_price,
                'quantity' => 1,
            ])->values(),
        ];
    });
@endphp

<x-app-layout title="Purchase Returns">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Purchase Returns (Supplier Return Out)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Return damaged or non-conforming goods to suppliers and record ledger Return Out movements.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $indexUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC]">
                    Overview
                </a>
                <a href="{{ $ordersUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#2563EB] hover:bg-[#F8FAFC]">
                    Purchase Orders
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data='{
        createOpen: {{ $errors->any() ? "true" : "false" }},
        selectedPoId: "",
        poMap: @json($poProductsMap),
        items: [],
        loadPoItems() {
            this.items = this.selectedPoId && this.poMap[this.selectedPoId]
                ? JSON.parse(JSON.stringify(this.poMap[this.selectedPoId]))
                : [];
        },
        removeItem(idx) {
            if (this.items.length > 1) this.items.splice(idx, 1);
        }
    }'>
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

        {{-- Filters --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
            <form method="GET" action="{{ $returnsUrl }}" class="flex flex-col md:flex-row md:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Search Return # / Reason</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="RET-2026..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">Filter</button>
                    <a href="{{ $returnsUrl }}" class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white text-slate-600 text-sm font-medium">Reset</a>
                    <button type="button" @click="createOpen = !createOpen" class="px-4 py-2 rounded-lg bg-[#DC2626] hover:bg-red-700 text-white text-sm font-semibold">
                        + Create Return
                    </button>
                </div>
            </form>
        </div>

        {{-- Create Return Form --}}
        <div x-show="createOpen" x-cloak class="bg-white border border-[#DCE3EA] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-[#142B4A]">New Purchase Return</h3>
                <button type="button" @click="createOpen = false" class="text-xs font-semibold text-slate-500 hover:text-[#DC2626]">Close</button>
            </div>
            <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Purchase Order</label>
                        <select name="purchase_order_id" x-model="selectedPoId" @change="loadPoItems()" required
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                            <option value="">Select Received PO...</option>
                            @foreach ($purchaseOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Return Reason</label>
                        <input type="text" name="reason" required placeholder="Defective batch, wrong specification..."
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                </div>

                <div x-show="items.length > 0" class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#142B4A]">Return Line Items</span>
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-[#F8FAFC] p-3 rounded-lg border border-[#DCE3EA]">
                            <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product_id">
                            <div class="md:col-span-6 text-sm font-semibold text-[#142B4A]" x-text="item.product_name"></div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-semibold text-slate-500">Return Qty</label>
                                <input type="number" min="1" :name="`items[${idx}][quantity]`" x-model="item.quantity" required
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-semibold text-slate-500">Unit Price</label>
                                <input type="number" step="0.01" min="0" :name="`items[${idx}][unit_price]`" x-model="item.unit_price" required
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                            </div>
                            <div class="md:col-span-1 text-right">
                                <button type="button" @click="removeItem(idx)" class="text-xs font-semibold text-[#DC2626] hover:underline">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="createOpen = false" class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#DC2626] hover:bg-red-700 text-white text-sm font-semibold">Execute Purchase Return</button>
                </div>
            </form>
        </div>

        {{-- Returns Table --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Return #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">PO / Supplier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Returned Items</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Total Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Reason</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Created By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($returns as $ret)
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3 font-mono font-bold text-[#DC2626]">
                                    {{ $ret->return_number }}
                                    <div class="text-[11px] font-sans font-normal text-slate-500">{{ $ret->created_at?->format('Y-m-d H:i') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-mono font-semibold text-[#2563EB]">{{ $ret->purchaseOrder->po_number ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $ret->supplier->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    @foreach ($ret->items as $item)
                                        <div>{{ $item->product->name ?? 'Product' }} &times; <span class="font-semibold">{{ $item->quantity }}</span></div>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-[#142B4A]">
                                    {{ number_format((float) $ret->total_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $ret->reason }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $ret->creator->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No purchase returns recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-[#DCE3EA]">
                {{ $returns->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
