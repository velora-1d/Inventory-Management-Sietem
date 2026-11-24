@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('procurement.index') ? route('procurement.index') : url('/procurement');
    $receiptsUrl = \Illuminate\Support\Facades\Route::has('procurement.receipts') ? route('procurement.receipts') : url('/procurement/receipts');
    $storeUrl = \Illuminate\Support\Facades\Route::has('procurement.receipts.store') ? route('procurement.receipts.store') : url('/procurement/receipts');
    $ordersUrl = \Illuminate\Support\Facades\Route::has('procurement.orders') ? route('procurement.orders') : url('/procurement/orders');

    $poItemCatalog = $receivableOrders->mapWithKeys(function ($po) {
        return [
            $po->id => $po->items->map(function ($item) {
                $remaining = max(0, (int) $item->quantity_ordered - (int) $item->quantity_received);
                return [
                    'purchase_order_item_id' => $item->id,
                    'product_name' => $item->product->name ?? 'Product',
                    'sku' => $item->product->sku ?? '-',
                    'ordered' => (int) $item->quantity_ordered,
                    'received' => (int) $item->quantity_received,
                    'remaining' => $remaining,
                    'quantity_received' => $remaining,
                    'quantity_accepted' => $remaining,
                    'quantity_rejected' => 0,
                ];
            })->values(),
        ];
    });
@endphp

<x-app-layout title="Goods Receipts">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Goods Receipts (GRN)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Inspect incoming supplier deliveries, record QC results, and post accepted stock into warehouse inventory.
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
        receiveOpen: {{ $errors->any() ? "true" : "false" }},
        selectedPoId: "",
        catalog: @json($poItemCatalog),
        lines: [],
        selectPo() {
            this.lines = this.selectedPoId && this.catalog[this.selectedPoId]
                ? JSON.parse(JSON.stringify(this.catalog[this.selectedPoId]))
                : [];
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

        {{-- Filters & Trigger --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
            <form method="GET" action="{{ $receiptsUrl }}" class="flex flex-col md:flex-row md:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Search GRN / PO Number</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="GRN-... or PO-..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">QC Status</label>
                    <select name="qc_status" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                        <option value="">All QC Statuses</option>
                        <option value="passed" @selected($qcStatus === 'passed')>Passed</option>
                        <option value="partial" @selected($qcStatus === 'partial')>Partial</option>
                        <option value="failed" @selected($qcStatus === 'failed')>Failed</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">Filter</button>
                    <a href="{{ $receiptsUrl }}" class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white text-slate-600 text-sm font-medium">Reset</a>
                    <button type="button" @click="receiveOpen = !receiveOpen" class="px-4 py-2 rounded-lg bg-[#0F766E] hover:bg-[#0D655E] text-white text-sm font-semibold">
                        + Record GRN
                    </button>
                </div>
            </form>
        </div>

        {{-- Record GRN Form --}}
        <div x-show="receiveOpen" x-cloak class="bg-white border border-[#DCE3EA] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-[#142B4A]">Record Goods Receipt (GRN)</h3>
                <button type="button" @click="receiveOpen = false" class="text-xs font-semibold text-slate-500 hover:text-[#DC2626]">Close</button>
            </div>
            <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Approved Purchase Order</label>
                        <select name="purchase_order_id" x-model="selectedPoId" @change="selectPo()" required
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                            <option value="">Select Purchase Order...</option>
                            @foreach ($receivableOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Destination Warehouse</label>
                        <select name="warehouse_id" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">QC Status</label>
                        <select name="qc_status" required class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                            <option value="passed">Passed</option>
                            <option value="partial">Partial</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Inspection Notes</label>
                    <input type="text" name="notes" placeholder="Delivery condition, seal check, batch details..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                </div>

                <div x-show="lines.length > 0" class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#142B4A]">Items to Receive</span>
                    <template x-for="(line, idx) in lines" :key="line.purchase_order_item_id">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-[#F8FAFC] p-3 rounded-lg border border-[#DCE3EA]">
                            <input type="hidden" :name="`items[${idx}][purchase_order_item_id]`" :value="line.purchase_order_item_id">
                            <div class="md:col-span-5">
                                <div class="text-sm font-semibold text-[#142B4A]" x-text="line.product_name"></div>
                                <div class="text-xs text-slate-500">Ordered: <span x-text="line.ordered"></span> &bull; Remaining: <span x-text="line.remaining"></span></div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-semibold text-slate-500">Received</label>
                                <input type="number" min="0" :max="line.remaining" :name="`items[${idx}][quantity_received]`" x-model="line.quantity_received"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-semibold text-[#10B981]">Accepted</label>
                                <input type="number" min="0" :max="line.quantity_received" :name="`items[${idx}][quantity_accepted]`" x-model="line.quantity_accepted"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-semibold text-[#DC2626]">Rejected</label>
                                <input type="number" min="0" :name="`items[${idx}][quantity_rejected]`" x-model="line.quantity_rejected"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="receiveOpen = false" class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#0F766E] hover:bg-[#0D655E] text-white text-sm font-semibold">Post Goods Receipt</button>
                </div>
            </form>
        </div>

        {{-- GRN Table --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">GRN Number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">PO / Supplier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Received Items</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">QC Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Receiver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($receipts as $grn)
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3 font-mono font-bold text-[#142B4A]">
                                    {{ $grn->grn_number }}
                                    <div class="text-[11px] font-sans font-normal text-slate-500">{{ $grn->received_at?->format('Y-m-d H:i') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-mono font-semibold text-[#2563EB]">{{ $grn->purchaseOrder->po_number ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $grn->purchaseOrder->supplier->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $grn->warehouse->name ?? 'Default' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    @foreach ($grn->items as $item)
                                        <div>
                                            {{ $item->product->name ?? 'Product' }}:
                                            <span class="font-semibold text-[#10B981]">{{ $item->quantity_accepted }} acc</span>
                                            @if ($item->quantity_rejected > 0)
                                                / <span class="font-semibold text-[#DC2626]">{{ $item->quantity_rejected }} rej</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        @if ($grn->qc_status === 'passed') bg-emerald-50 text-[#10B981] border border-emerald-200
                                        @elseif ($grn->qc_status === 'failed') bg-red-50 text-[#DC2626] border border-red-200
                                        @else bg-amber-50 text-[#D97706] border border-amber-200 @endif">
                                        {{ ucfirst($grn->qc_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $grn->receiver->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No goods receipts recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-[#DCE3EA]">
                {{ $receipts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
