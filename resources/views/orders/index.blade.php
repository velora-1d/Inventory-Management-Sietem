@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('orders.index') ? route('orders.index') : url('/orders');
@endphp

<x-app-layout title="Customer Orders">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Customer Orders Management
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Verifikasi pembayaran customer, pantau status fulfillment, dan kelola pembatalan pesanan.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-[#0F766E]">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-[#DC2626]">
                {{ session('error') }}
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Orders</p>
                <p class="mt-1 text-2xl font-bold text-[#142B4A]">{{ number_format($summary['total_orders']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Payment</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ number_format($summary['pending_payment']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Waiting Verification</p>
                <p class="mt-1 text-2xl font-bold text-[#2563EB]">{{ number_format($summary['waiting_verification']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Confirmed &amp; Active</p>
                <p class="mt-1 text-2xl font-bold text-[#10B981]">{{ number_format($summary['confirmed_active']) }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
            <form method="GET" action="{{ $indexUrl }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari Order # / nama / email customer..."
                       class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />

                <select name="status" class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                    <option value="">Semua Status Pesanan</option>
                    @foreach(['pending_payment', 'confirmed', 'processing', 'ready_to_ship', 'shipped', 'delivered', 'completed', 'cancelled'] as $st)
                        <option value="{{ $st }}" @selected($status === $st)>{{ strtoupper(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>

                <select name="payment_status" class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                    <option value="">Semua Status Pembayaran</option>
                    @foreach(['unpaid', 'waiting_verification', 'paid', 'failed'] as $pst)
                        <option value="{{ $pst }}" @selected($paymentStatus === $pst)>{{ strtoupper(str_replace('_', ' ', $pst)) }}</option>
                    @endforeach
                </select>

                <button type="submit"
                        class="rounded-lg bg-[#142B4A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1E3A5F]">
                    Terapkan Filter
                </button>
            </form>
        </div>

        {{-- Orders Table --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[#DCE3EA] bg-[#F8FAFC] text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Order #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA]">
                        @forelse($orders as $order)
                            @php
                                $showUrl = \Illuminate\Support\Facades\Route::has('orders.show')
                                    ? route('orders.show', $order)
                                    : url('/orders/' . $order->id);
                            @endphp
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-5 py-3.5 font-bold text-[#142B4A]">
                                    {{ $order->order_number }}
                                    <p class="text-[11px] font-normal text-slate-400">{{ $order->created_at?->format('d M Y H:i') }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-[#142B4A]">{{ $order->user?->name ?? $order->customer?->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $order->user?->email ?? $order->customer?->email ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">{{ $order->items->sum('quantity') }} pcs</td>
                                <td class="px-4 py-3.5">
                                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $order->statusBadgeClasses() }}">
                                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $order->paymentBadgeClasses() }}">
                                        {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-[#142B4A]">{{ format_money($order->total) }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="{{ $showUrl }}"
                                       class="inline-flex rounded-lg border border-[#DCE3EA] bg-white px-3 py-1.5 text-xs font-semibold text-[#2563EB] hover:bg-blue-50">
                                        Kelola
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Belum ada customer order.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[#DCE3EA] px-5 py-3">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
