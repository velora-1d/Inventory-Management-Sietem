@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('procurement.index') ? route('procurement.index') : url('/procurement');
    $ordersUrl = \Illuminate\Support\Facades\Route::has('procurement.orders') ? route('procurement.orders') : url('/procurement/orders');
    $storeUrl = \Illuminate\Support\Facades\Route::has('procurement.orders.store') ? route('procurement.orders.store') : url('/procurement/orders');
    $receiptsUrl = \Illuminate\Support\Facades\Route::has('procurement.receipts') ? route('procurement.receipts') : url('/procurement/receipts');
@endphp

<x-app-layout title="Purchase Orders">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Purchase Orders (PO)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Draft, submit, revise, and approve Purchase Orders with strict Maker-Checker segregation.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $indexUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC]">
                    Overview
                </a>
                <a href="{{ $receiptsUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-[#0F766E] text-white hover:bg-[#0D655E]">
                    Goods Receipts
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        createOpen: {{ $errors->any() ? 'true' : 'false' }},
        reviseOpen: false,
        reviseAction: '',
        revisePoNumber: '',
        reviseItems: [{ product_id: '', quantity_ordered: 1, unit_price: 0 }],
        items: [{ product_id: '', quantity_ordered: 1, unit_price: 0 }],
        addItem() { this.items.push({ product_id: '', quantity_ordered: 1, unit_price: 0 }); },
        removeItem(idx) { if (this.items.length > 1) this.items.splice(idx, 1); },
        addReviseItem() { this.reviseItems.push({ product_id: '', quantity_ordered: 1, unit_price: 0 }); },
        removeReviseItem(idx) { if (this.reviseItems.length > 1) this.reviseItems.splice(idx, 1); },
        openRevise(url, poNumber, poItems) {
            this.reviseAction = url;
            this.revisePoNumber = poNumber;
            this.reviseItems = poItems.length ? poItems : [{ product_id: '', quantity_ordered: 1, unit_price: 0 }];
            this.reviseOpen = true;
        }
    }">
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
            <form method="GET" action="{{ $ordersUrl }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Search PO Number / Notes</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="PO-2026..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                </div>
                <div class="w-full lg:w-48">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Supplier</label>
                    <select name="supplier_id" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($supplierId === $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full lg:w-48">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        @foreach (['draft', 'pending_approval', 'approved', 'sent', 'partially_received', 'fully_received', 'cancelled'] as $st)
                            <option value="{{ $st }}" @selected($status === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">Filter</button>
                    <a href="{{ $ordersUrl }}" class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white text-slate-600 text-sm font-medium">Reset</a>
                    <button type="button" @click="createOpen = !createOpen" class="px-4 py-2 rounded-lg bg-[#142B4A] hover:bg-[#0F223B] text-white text-sm font-semibold">
                        + New PO
                    </button>
                </div>
            </form>
        </div>

        {{-- Create PO Form --}}
        <div x-show="createOpen" x-cloak class="bg-white border border-[#DCE3EA] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-[#142B4A]">Create Draft Purchase Order</h3>
                <button type="button" @click="createOpen = false" class="text-xs font-semibold text-slate-500 hover:text-[#DC2626]">Close</button>
            </div>
            <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Supplier</label>
                        <select name="supplier_id" required class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                            <option value="">Select supplier...</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Linked Approved PR (Optional)</label>
                        <select name="purchase_request_id" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                            <option value="">Direct Purchase Order</option>
                            @foreach ($approvedRequests as $pr)
                                <option value="{{ $pr->id }}">{{ $pr->pr_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Expected Delivery Date</label>
                        <input type="date" name="expected_date" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Notes</label>
                    <input type="text" name="notes" placeholder="Delivery terms or special instructions..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#142B4A]">PO Line Items</span>
                        <button type="button" @click="addItem()" class="text-xs font-semibold text-[#2563EB] hover:underline">+ Add Line</button>
                    </div>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-[#F8FAFC] p-3 rounded-lg border border-[#DCE3EA]">
                            <div class="md:col-span-6">
                                <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                        class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm">
                                    <option value="">Select product...</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <input type="number" min="1" :name="`items[${index}][quantity_ordered]`" x-model="item.quantity_ordered" required placeholder="Qty"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-3">
                                <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model="item.unit_price" required placeholder="Unit Price"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-1 text-right">
                                <button type="button" @click="removeItem(index)" class="text-xs font-semibold text-[#DC2626] hover:underline">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="createOpen = false" class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">Create Draft PO</button>
                </div>
            </form>
        </div>

        {{-- PO Table --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">PO Number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Supplier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Items</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Maker / Checker</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($orders as $po)
                            @php
                                $submitUrl = \Illuminate\Support\Facades\Route::has('procurement.orders.submit')
                                    ? route('procurement.orders.submit', $po)
                                    : url("/procurement/orders/{$po->id}/submit");
                                $approveUrl = \Illuminate\Support\Facades\Route::has('procurement.orders.approve')
                                    ? route('procurement.orders.approve', $po)
                                    : url("/procurement/orders/{$po->id}/approve");
                                $reviseUrl = \Illuminate\Support\Facades\Route::has('procurement.orders.revise')
                                    ? route('procurement.orders.revise', $po)
                                    : url("/procurement/orders/{$po->id}/revise");
                                $poItemsJson = $po->items->map(fn ($i) => [
                                    'product_id' => $i->product_id,
                                    'quantity_ordered' => $i->quantity_ordered,
                                    'unit_price' => (float) $i->unit_price,
                                ])->values();
                            @endphp
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3 font-mono font-bold text-[#142B4A]">
                                    {{ $po->po_number }}
                                    <div class="text-[11px] font-sans font-normal text-slate-500">Rev #{{ $po->revision_number }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="font-semibold text-[#142B4A]">{{ $po->supplier->name ?? '-' }}</div>
                                    @if ($po->purchaseRequest)
                                        <div class="text-xs text-slate-500 font-mono">PR: {{ $po->purchaseRequest->pr_number }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    @foreach ($po->items as $item)
                                        <div>{{ $item->product->name ?? 'Product' }}: {{ $item->quantity_received }}/{{ $item->quantity_ordered }}</div>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-[#142B4A]">
                                    {{ number_format((float) $po->total, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        @if (in_array($po->status, ['approved', 'fully_received'])) bg-emerald-50 text-[#10B981] border border-emerald-200
                                        @elseif ($po->status === 'pending_approval') bg-amber-50 text-[#D97706] border border-amber-200
                                        @elseif ($po->status === 'partially_received') bg-blue-50 text-[#2563EB] border border-blue-200
                                        @else bg-slate-100 text-slate-700 border border-[#DCE3EA] @endif">
                                        {{ str_replace('_', ' ', ucfirst($po->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div>Maker: {{ $po->creator->name ?? '-' }}</div>
                                    <div>Checker: {{ $po->approver->name ?? 'Pending' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-1.5">
                                        @if ($po->status === 'draft')
                                            <form method="POST" action="{{ $submitUrl }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-semibold">
                                                    Submit
                                                </button>
                                            </form>
                                        @endif

                                        @if ($po->status === 'pending_approval')
                                            @if ((int) $po->created_by !== (int) auth()->id())
                                                <form method="POST" action="{{ $approveUrl }}">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#10B981] hover:bg-[#0F766E] text-white text-xs font-semibold">
                                                        Approve
                                                    </button>
                                                </form>
                                            @else
                                                <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-[#D97706] text-[11px] font-semibold">
                                                    Maker cannot approve
                                                </span>
                                            @endif
                                        @endif

                                        @if (in_array($po->status, ['draft', 'pending_approval', 'approved', 'sent']))
                                            <button type="button"
                                                    @click='openRevise("{{ $reviseUrl }}", "{{ $po->po_number }}", @json($poItemsJson))'
                                                    class="px-3 py-1.5 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-[#142B4A] text-xs font-semibold">
                                                Revise
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No purchase orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-[#DCE3EA]">
                {{ $orders->links() }}
            </div>
        </div>

        {{-- Revise PO Modal --}}
        <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white border border-[#DCE3EA] rounded-xl max-w-2xl w-full p-5 shadow-xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-base font-bold text-[#142B4A]">Revise Purchase Order <span class="font-mono" x-text="revisePoNumber"></span></h3>
                <form method="POST" :action="reviseAction" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Revision Reason</label>
                        <input type="text" name="reason" required placeholder="Price update or quantity change..."
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase text-[#142B4A]">Updated Items</span>
                            <button type="button" @click="addReviseItem()" class="text-xs font-semibold text-[#2563EB] hover:underline">+ Add Line</button>
                        </div>
                        <template x-for="(item, idx) in reviseItems" :key="idx">
                            <div class="grid grid-cols-12 gap-2 items-center bg-[#F8FAFC] p-2.5 rounded-lg border border-[#DCE3EA]">
                                <div class="col-span-6">
                                    <select :name="`items[${idx}][product_id]`" x-model="item.product_id" required
                                            class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" min="1" :name="`items[${idx}][quantity_ordered]`" x-model="item.quantity_ordered" required
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" step="0.01" min="0" :name="`items[${idx}][unit_price]`" x-model="item.unit_price" required
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-sm">
                                </div>
                                <div class="col-span-1 text-right">
                                    <button type="button" @click="removeReviseItem(idx)" class="text-xs text-[#DC2626] font-semibold">X</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="reviseOpen = false" class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-[#D97706] text-white text-xs font-semibold">Save Revision &amp; Re-Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
