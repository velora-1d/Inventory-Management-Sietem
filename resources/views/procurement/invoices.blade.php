@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('procurement.index') ? route('procurement.index') : url('/procurement');
    $invoicesUrl = \Illuminate\Support\Facades\Route::has('procurement.invoices') ? route('procurement.invoices') : url('/procurement/invoices');
    $storeUrl = \Illuminate\Support\Facades\Route::has('procurement.invoices.store') ? route('procurement.invoices.store') : url('/procurement/invoices');
    $ordersUrl = \Illuminate\Support\Facades\Route::has('procurement.orders') ? route('procurement.orders') : url('/procurement/orders');
@endphp

<x-app-layout title="Supplier Invoices & 3-Way Match">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Supplier Invoices &amp; 3-Way Match
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Verify supplier invoices automatically against Purchase Order pricing and accepted Goods Receipt quantities.
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

    <div class="space-y-6" x-data="{ createOpen: {{ $errors->any() ? 'true' : 'false' }} }">
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
            <form method="GET" action="{{ $invoicesUrl }}" class="flex flex-col md:flex-row md:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Search Invoice / PO Number</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="INV-... or PO-..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">3-Way Match Status</label>
                    <select name="match_status" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                        <option value="">All Match Statuses</option>
                        <option value="matched" @selected($matchStatus === 'matched')>Matched</option>
                        <option value="discrepancy" @selected($matchStatus === 'discrepancy')>Discrepancy</option>
                        <option value="pending" @selected($matchStatus === 'pending')>Pending</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">Filter</button>
                    <a href="{{ $invoicesUrl }}" class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white text-slate-600 text-sm font-medium">Reset</a>
                    <button type="button" @click="createOpen = !createOpen" class="px-4 py-2 rounded-lg bg-[#142B4A] hover:bg-[#0F223B] text-white text-sm font-semibold">
                        + Record Invoice
                    </button>
                </div>
            </form>
        </div>

        {{-- Record Invoice Form --}}
        <div x-show="createOpen" x-cloak class="bg-white border border-[#DCE3EA] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-[#142B4A]">Record Supplier Invoice</h3>
                <button type="button" @click="createOpen = false" class="text-xs font-semibold text-slate-500 hover:text-[#DC2626]">Close</button>
            </div>
            <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Purchase Order</label>
                        <select name="purchase_order_id" required class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                            <option value="">Select PO...</option>
                            @foreach ($purchaseOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->name ?? '' }} (Total: {{ number_format((float) $po->total, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Supplier Invoice Number</label>
                        <input type="text" name="invoice_number" required placeholder="INV-SUP-001"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Invoice Amount</label>
                        <input type="number" step="0.01" min="0" name="amount" required placeholder="0.00"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Invoice Date</label>
                        <input type="date" name="invoice_date" value="{{ now()->toDateString() }}" required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Due Date (Optional)</label>
                        <input type="date" name="due_date"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Match Notes (Optional)</label>
                        <input type="text" name="match_notes" placeholder="Reference or remarks..."
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="createOpen = false" class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">Run 3-Way Match &amp; Save</button>
                </div>
            </form>
        </div>

        {{-- Invoices Table --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Invoice #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">PO / Supplier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Dates</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">3-Way Match</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Match Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($invoices as $inv)
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3 font-mono font-bold text-[#142B4A]">
                                    {{ $inv->invoice_number }}
                                    <div class="text-[11px] font-sans font-normal text-slate-500">Status: {{ ucfirst($inv->status) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-mono font-semibold text-[#2563EB]">{{ $inv->purchaseOrder->po_number ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $inv->supplier->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div>Inv: {{ $inv->invoice_date?->format('Y-m-d') }}</div>
                                    <div>Due: {{ $inv->due_date?->format('Y-m-d') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-[#142B4A]">
                                    {{ number_format((float) $inv->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        @if ($inv->match_status === 'matched') bg-emerald-50 text-[#10B981] border border-emerald-200
                                        @elseif ($inv->match_status === 'discrepancy') bg-red-50 text-[#DC2626] border border-red-200
                                        @else bg-amber-50 text-[#D97706] border border-amber-200 @endif">
                                        {{ ucfirst($inv->match_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ $inv->match_notes ?: '-' }}
                                    @if ($inv->verifier)
                                        <div class="text-[11px] text-[#0F766E] mt-0.5">Verified by {{ $inv->verifier->name }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No supplier invoices recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-[#DCE3EA]">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
