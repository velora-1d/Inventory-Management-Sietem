@php
    $reportsIndexUrl = \Illuminate\Support\Facades\Route::has('reports.index')
        ? route('reports.index')
        : url('/reports');
    $hasExportRoute = \Illuminate\Support\Facades\Route::has('reports.export');
    $exportUrl = function (string $type) use ($hasExportRoute, $from, $to): string {
        $params = array_filter([
            'type' => $type,
            'from' => $from,
            'to' => $to,
        ], fn ($v) => $v !== null && $v !== '');

        return $hasExportRoute
            ? route('reports.export', $params)
            : url('/reports/export') . '?' . http_build_query($params);
    };
@endphp

<x-app-layout title="Executive Reports">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Executive Reports &amp; Analytics
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Perbandingan penjualan POS vs Customer Portal, valuasi stok, procurement &amp; AP, serta produktivitas SLA tim.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $exportUrl('sales') }}"
                   class="inline-flex items-center rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-xs font-semibold text-[#142B4A] hover:bg-[#F8FAFC]">
                    Export Sales CSV
                </a>
                <a href="{{ $exportUrl('stock') }}"
                   class="inline-flex items-center rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-xs font-semibold text-[#142B4A] hover:bg-[#F8FAFC]">
                    Export Stock CSV
                </a>
                <a href="{{ $exportUrl('procurement') }}"
                   class="inline-flex items-center rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-xs font-semibold text-[#142B4A] hover:bg-[#F8FAFC]">
                    Export Procurement CSV
                </a>
                <a href="{{ $exportUrl('sla') }}"
                   class="inline-flex items-center rounded-lg bg-[#0F766E] px-3 py-2 text-xs font-semibold text-white hover:bg-[#0D655E]">
                    Export SLA CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Date Range Filter --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
            <form method="GET" action="{{ $reportsIndexUrl }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4 items-end">
                <div>
                    <label for="from" class="block text-xs font-semibold text-[#142B4A] mb-1">Dari Tanggal</label>
                    <input id="from"
                           type="date"
                           name="from"
                           value="{{ $from }}"
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                </div>
                <div>
                    <label for="to" class="block text-xs font-semibold text-[#142B4A] mb-1">Sampai Tanggal</label>
                    <input id="to"
                           type="date"
                           name="to"
                           value="{{ $to }}"
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                </div>
                <div class="sm:col-span-2 flex items-center gap-2">
                    <button type="submit"
                            class="rounded-lg bg-[#2563EB] px-5 py-2 text-sm font-semibold text-white hover:bg-[#1D4ED8]">
                        Terapkan Periode
                    </button>
                    @if($from || $to)
                        <a href="{{ $reportsIndexUrl }}"
                           class="rounded-lg border border-[#DCE3EA] bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-[#F8FAFC]">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Section 1: Sales Comparison --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-[#142B4A]">1. Sales Channel Comparison (POS vs Customer Portal)</h3>
                    <p class="text-xs text-slate-500">Perbandingan volume transaksi dan omzet antar channel penjualan.</p>
                </div>
                <a href="{{ $exportUrl('sales') }}" class="text-xs font-semibold text-[#2563EB] hover:underline">Download CSV</a>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">POS Kasir</p>
                    <p class="mt-1 text-2xl font-bold text-[#2563EB]">{{ format_money($salesComparison['pos_sales_total']) }}</p>
                    <p class="text-xs text-slate-600 mt-1">{{ number_format($salesComparison['pos_sales_count']) }} transaksi POS</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer Portal</p>
                    <p class="mt-1 text-2xl font-bold text-[#0F766E]">{{ format_money($salesComparison['portal_orders_total']) }}</p>
                    <p class="text-xs text-slate-600 mt-1">{{ number_format($salesComparison['portal_orders_count']) }} pesanan portal</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#142B4A] p-4 text-white">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-300">Total Gabungan</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ format_money($salesComparison['combined_total']) }}</p>
                    <p class="text-xs text-slate-300 mt-1">{{ number_format($salesComparison['combined_count']) }} total transaksi</p>
                </div>
            </div>
        </div>

        {{-- Section 2: Stock Valuation & Movements --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-[#142B4A]">2. Stock Valuation &amp; Movement Summary</h3>
                    <p class="text-xs text-slate-500">Valuasi persediaan berdasarkan harga beli (on_hand &times; purchase_price).</p>
                </div>
                <a href="{{ $exportUrl('stock') }}" class="text-xs font-semibold text-[#2563EB] hover:underline">Download CSV</a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-5">
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Total Stock Value</p>
                    <p class="mt-1 text-xl font-bold text-[#142B4A]">{{ format_money($stockValuation['stock_value']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Total SKUs</p>
                    <p class="mt-1 text-xl font-bold text-[#2563EB]">{{ number_format($stockValuation['total_skus']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Total On-Hand Qty</p>
                    <p class="mt-1 text-xl font-bold text-[#10B981]">{{ number_format($stockValuation['total_on_hand']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Total Reserved Qty</p>
                    <p class="mt-1 text-xl font-bold text-[#D97706]">{{ number_format($stockValuation['total_reserved']) }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[#DCE3EA] bg-[#F8FAFC] text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-2.5">Tipe Movement</th>
                            <th class="px-4 py-2.5 text-right">Jumlah Aktivitas</th>
                            <th class="px-4 py-2.5 text-right">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA]">
                        @forelse($stockValuation['movements_by_type'] as $mv)
                            <tr>
                                <td class="px-4 py-2.5 font-semibold text-[#142B4A]">{{ $mv['type'] }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-700">{{ number_format($mv['count']) }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-[#2563EB]">{{ number_format($mv['total_qty']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-xs text-slate-500">Belum ada pergerakan stok pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 3: Procurement & AP Summary --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-[#142B4A]">3. Procurement &amp; Accounts Payable Summary</h3>
                    <p class="text-xs text-slate-500">Ringkasan nilai pembelian (PO) dan posisi pembayaran hutang supplier.</p>
                </div>
                <a href="{{ $exportUrl('procurement') }}" class="text-xs font-semibold text-[#2563EB] hover:underline">Download CSV</a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Total PO ({{ number_format($procurementSummary['po_count']) }})</p>
                    <p class="mt-1 text-xl font-bold text-[#142B4A]">{{ format_money($procurementSummary['po_total']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">AP Tercatat ({{ number_format($procurementSummary['ap_count']) }})</p>
                    <p class="mt-1 text-xl font-bold text-[#2563EB]">{{ format_money($procurementSummary['ap_total_amount']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">AP Terbayar</p>
                    <p class="mt-1 text-xl font-bold text-[#10B981]">{{ format_money($procurementSummary['ap_paid_total']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Sisa Hutang (AP Due)</p>
                    <p class="mt-1 text-xl font-bold text-[#DC2626]">{{ format_money($procurementSummary['ap_total_due']) }}</p>
                </div>
            </div>
        </div>

        {{-- Section 4: Team SLA Productivity --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-[#142B4A]">4. Team SLA Productivity</h3>
                    <p class="text-xs text-slate-500">Kinerja penyelesaian tugas operasional order terhadap target SLA.</p>
                </div>
                <a href="{{ $exportUrl('sla') }}" class="text-xs font-semibold text-[#2563EB] hover:underline">Download CSV</a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-5">
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Total Tasks</p>
                    <p class="mt-1 text-xl font-bold text-[#142B4A]">{{ number_format($teamSla['total_tasks']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Completed</p>
                    <p class="mt-1 text-xl font-bold text-[#10B981]">{{ number_format($teamSla['completed_tasks']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Overdue</p>
                    <p class="mt-1 text-xl font-bold text-[#DC2626]">{{ number_format($teamSla['overdue_tasks']) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase text-slate-500">Completion Rate</p>
                    <p class="mt-1 text-xl font-bold text-[#0F766E]">{{ $teamSla['completion_rate'] }}%</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[#DCE3EA] bg-[#F8FAFC] text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-4 py-2.5">Departemen</th>
                            <th class="px-4 py-2.5 text-right">Total Task</th>
                            <th class="px-4 py-2.5 text-right">Selesai</th>
                            <th class="px-4 py-2.5 text-right">Overdue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA]">
                        @forelse($teamSla['by_department'] as $dept)
                            <tr>
                                <td class="px-4 py-2.5 font-semibold text-[#142B4A] uppercase">{{ $dept['department'] }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-700">{{ number_format($dept['total']) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-[#10B981]">{{ number_format($dept['completed']) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-[#DC2626]">{{ number_format($dept['overdue']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-xs text-slate-500">Belum ada task operasional pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
