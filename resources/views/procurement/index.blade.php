@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('procurement.index') ? route('procurement.index') : url('/procurement');
    $requestsUrl = \Illuminate\Support\Facades\Route::has('procurement.requests') ? route('procurement.requests') : url('/procurement/requests');
    $ordersUrl = \Illuminate\Support\Facades\Route::has('procurement.orders') ? route('procurement.orders') : url('/procurement/orders');
    $receiptsUrl = \Illuminate\Support\Facades\Route::has('procurement.receipts') ? route('procurement.receipts') : url('/procurement/receipts');
    $invoicesUrl = \Illuminate\Support\Facades\Route::has('procurement.invoices') ? route('procurement.invoices') : url('/procurement/invoices');
    $returnsUrl = \Illuminate\Support\Facades\Route::has('procurement.returns') ? route('procurement.returns') : url('/procurement/returns');
@endphp

<x-app-layout title="Procurement Hub">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Procurement &amp; Supply Chain Control
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    End-to-end Purchase Requests, Maker-Checker Purchase Orders, Goods Receipts, 3-Way Match Invoices, and Supplier Returns.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $requestsUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    Requests
                </a>
                <a href="{{ $ordersUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-[#2563EB] text-white hover:bg-[#1D4ED8] transition-colors">
                    Purchase Orders
                </a>
                <a href="{{ $receiptsUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-[#0F766E] text-white hover:bg-[#0D655E] transition-colors">
                    Goods Receipts
                </a>
                <a href="{{ $invoicesUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    3-Way Invoices
                </a>
                <a href="{{ $returnsUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#DC2626] hover:bg-red-50 transition-colors">
                    Returns
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
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

        {{-- KPI Strip --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ $requestsUrl }}" class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm hover:border-[#2563EB] transition-colors">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Requests</p>
                <p class="text-2xl font-bold text-[#D97706] mt-1">{{ number_format($summary['pending_requests']) }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ number_format($summary['approved_requests']) }} approved PRs ready</p>
            </a>

            <a href="{{ $ordersUrl }}" class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm hover:border-[#2563EB] transition-colors">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending PO Approval</p>
                <p class="text-2xl font-bold text-[#2563EB] mt-1">{{ number_format($summary['pending_orders']) }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ number_format($summary['open_orders']) }} active receiving POs</p>
            </a>

            <a href="{{ $receiptsUrl }}" class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm hover:border-[#0F766E] transition-colors">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Goods Receipts (GRN)</p>
                <p class="text-2xl font-bold text-[#0F766E] mt-1">{{ number_format($summary['goods_receipts']) }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ number_format($summary['purchase_returns']) }} supplier returns</p>
            </a>

            <a href="{{ $invoicesUrl }}" class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm hover:border-[#10B981] transition-colors">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">3-Way Match Invoices</p>
                <div class="flex items-baseline gap-3 mt-1">
                    <span class="text-2xl font-bold text-[#10B981]">{{ number_format($summary['matched_invoices']) }}</span>
                    <span class="text-xs font-semibold text-[#DC2626]">{{ number_format($summary['discrepancy_invoices']) }} discrepancy</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">PO vs GRN vs Invoice verification</p>
            </a>
        </div>

        {{-- Recent Activity Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Recent Purchase Orders --}}
            <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-[#DCE3EA] flex items-center justify-between bg-[#F8FAFC]">
                    <h3 class="text-sm font-bold text-[#142B4A]">Recent Purchase Orders</h3>
                    <a href="{{ $ordersUrl }}" class="text-xs font-semibold text-[#2563EB] hover:underline">View All</a>
                </div>
                <div class="divide-y divide-[#DCE3EA]">
                    @forelse ($recentOrders as $po)
                        <div class="px-5 py-3 flex items-center justify-between gap-4 hover:bg-[#F8FAFC]">
                            <div>
                                <div class="text-sm font-bold text-[#142B4A] font-mono">{{ $po->po_number }}</div>
                                <div class="text-xs text-slate-500">{{ $po->supplier->name ?? 'Supplier' }} &bull; Rev #{{ $po->revision_number }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-[#142B4A]">{{ number_format((float) $po->total, 2) }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                    @if (in_array($po->status, ['approved', 'fully_received'])) bg-emerald-50 text-[#0F766E] border border-emerald-200
                                    @elseif ($po->status === 'pending_approval') bg-amber-50 text-[#D97706] border border-amber-200
                                    @elseif ($po->status === 'partially_received') bg-blue-50 text-[#2563EB] border border-blue-200
                                    @else bg-slate-100 text-slate-700 border border-[#DCE3EA] @endif">
                                    {{ str_replace('_', ' ', ucfirst($po->status)) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-500">No purchase orders recorded yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Purchase Requests & Invoices --}}
            <div class="space-y-6">
                <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#DCE3EA] flex items-center justify-between bg-[#F8FAFC]">
                        <h3 class="text-sm font-bold text-[#142B4A]">Recent Purchase Requests</h3>
                        <a href="{{ $requestsUrl }}" class="text-xs font-semibold text-[#2563EB] hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-[#DCE3EA]">
                        @forelse ($recentRequests as $pr)
                            <div class="px-5 py-3 flex items-center justify-between gap-4 hover:bg-[#F8FAFC]">
                                <div>
                                    <div class="text-sm font-bold text-[#142B4A] font-mono">{{ $pr->pr_number }}</div>
                                    <div class="text-xs text-slate-500">By {{ $pr->requester->name ?? 'User' }} &bull; {{ $pr->items->count() }} item(s)</div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold
                                    @if ($pr->status === 'approved') bg-emerald-50 text-[#0F766E] border border-emerald-200
                                    @elseif ($pr->status === 'rejected') bg-red-50 text-[#DC2626] border border-red-200
                                    @else bg-amber-50 text-[#D97706] border border-amber-200 @endif">
                                    {{ str_replace('_', ' ', ucfirst($pr->status)) }}
                                </span>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-slate-500">No purchase requests yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#DCE3EA] flex items-center justify-between bg-[#F8FAFC]">
                        <h3 class="text-sm font-bold text-[#142B4A]">Recent Supplier Invoices</h3>
                        <a href="{{ $invoicesUrl }}" class="text-xs font-semibold text-[#2563EB] hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-[#DCE3EA]">
                        @forelse ($recentInvoices as $inv)
                            <div class="px-5 py-3 flex items-center justify-between gap-4 hover:bg-[#F8FAFC]">
                                <div>
                                    <div class="text-sm font-bold text-[#142B4A] font-mono">{{ $inv->invoice_number }}</div>
                                    <div class="text-xs text-slate-500">{{ $inv->purchaseOrder->po_number ?? '-' }} &bull; {{ $inv->supplier->name ?? '-' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-[#142B4A]">{{ number_format((float) $inv->amount, 2) }}</div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                        @if ($inv->match_status === 'matched') bg-emerald-50 text-[#10B981] border border-emerald-200
                                        @elseif ($inv->match_status === 'discrepancy') bg-red-50 text-[#DC2626] border border-red-200
                                        @else bg-amber-50 text-[#D97706] border border-amber-200 @endif">
                                        {{ ucfirst($inv->match_status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-sm text-slate-500">No supplier invoices recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
