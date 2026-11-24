@php
    $ordersIndexUrl = \Illuminate\Support\Facades\Route::has('orders.index') ? route('orders.index') : url('/orders');
    $verifyUrl = \Illuminate\Support\Facades\Route::has('orders.verify-payment') ? route('orders.verify-payment', $order) : url('/orders/' . $order->id . '/verify-payment');
    $cancelUrl = \Illuminate\Support\Facades\Route::has('orders.cancel') ? route('orders.cancel', $order) : url('/orders/' . $order->id . '/cancel');
@endphp

<x-app-layout title="Order #{{ $order->order_number }}">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Order #{{ $order->order_number }}
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Customer: {{ $order->user?->name ?? '-' }} ({{ $order->user?->email ?? '-' }})
                </p>
            </div>
            <a href="{{ $ordersIndexUrl }}"
               class="inline-flex items-center rounded-lg border border-[#DCE3EA] bg-white px-3.5 py-2 text-xs font-semibold text-[#142B4A] hover:bg-[#F8FAFC]">
                &larr; Kembali ke Daftar Order
            </a>
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Order Status</p>
                <span class="mt-2 inline-block rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $order->statusBadgeClasses() }}">
                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                </span>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Payment Status</p>
                <span class="mt-2 inline-block rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $order->paymentBadgeClasses() }}">
                    {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                </span>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Delivery Type</p>
                <p class="mt-2 text-sm font-bold text-[#142B4A]">{{ strtoupper($order->delivery_type) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total</p>
                <p class="mt-1.5 text-xl font-extrabold text-[#142B4A]">{{ format_money($order->total) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            {{-- Items & Status History --}}
            <div class="lg:col-span-7 space-y-6">
                <div class="rounded-xl border border-[#DCE3EA] bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-[#DCE3EA] px-5 py-3.5">
                        <h3 class="text-sm font-bold text-[#142B4A]">Order Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-[#F8FAFC] text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Product</th>
                                    <th class="px-4 py-3 text-center">Qty</th>
                                    <th class="px-4 py-3 text-right">Unit Price</th>
                                    <th class="px-5 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#DCE3EA]">
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="px-5 py-3">
                                            <p class="font-semibold text-[#142B4A]">{{ $item->product_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $item->sku ?: '-' }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-center font-semibold">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-right">{{ format_money($item->unit_price) }}</td>
                                        <td class="px-5 py-3 text-right font-bold text-[#142B4A]">{{ format_money($item->subtotal) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-[#142B4A]">Status History</h3>
                    <div class="mt-3 space-y-2.5">
                        @foreach($order->statusHistories as $history)
                            <div class="border-l-2 border-[#2563EB] pl-3">
                                <p class="text-xs font-bold text-[#142B4A]">
                                    {{ strtoupper(str_replace('_', ' ', $history->to_status)) }}
                                </p>
                                <p class="text-xs text-slate-600">
                                    {{ $history->reason ?: '-' }}
                                    @if($history->changer)
                                        &middot; oleh {{ $history->changer->name }}
                                    @endif
                                </p>
                                <p class="text-[11px] text-slate-400">{{ $history->created_at?->format('d M Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Admin Verification & Cancellation --}}
            <div class="lg:col-span-5 space-y-6">
                @if($order->address)
                    <div class="rounded-xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-[#142B4A]">Alamat Pengiriman</h3>
                        <p class="mt-2 text-sm font-semibold text-[#142B4A]">{{ $order->address->recipient_name }} ({{ $order->address->phone }})</p>
                        <p class="mt-1 text-xs text-slate-600">{{ $order->address->formattedAddress() }}</p>
                    </div>
                @endif

                <div class="rounded-xl border border-[#DCE3EA] bg-white p-5 shadow-sm space-y-3">
                    <h3 class="text-sm font-bold text-[#142B4A]">Verifikasi Pembayaran</h3>
                    @if($order->latestPayment)
                        <div class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] p-3 text-xs space-y-1">
                            <p>Metode: <strong>{{ strtoupper($order->latestPayment->payment_method) }}</strong> ({{ $order->latestPayment->gateway }})</p>
                            <p>Nominal: <strong>{{ format_money($order->latestPayment->amount) }}</strong></p>
                            <p>Status Payment: <strong>{{ strtoupper($order->latestPayment->status) }}</strong></p>
                            @if($order->latestPayment->proof_path)
                                <p class="text-[#0F766E]">Bukti tersimpan (private disk): <code>{{ $order->latestPayment->proof_path }}</code></p>
                            @endif
                        </div>
                    @endif

                    @if($order->status !== 'cancelled' && $order->payment_status !== 'paid')
                        <form method="POST" action="{{ $verifyUrl }}" class="space-y-3">
                            @csrf
                            <input type="text" name="reason" placeholder="Catatan verifikasi (opsional)..."
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-xs text-[#142B4A]" />
                            <div class="flex gap-2">
                                <button type="submit" name="decision" value="approve"
                                        class="flex-1 rounded-lg bg-[#10B981] px-3 py-2 text-xs font-bold text-white hover:bg-emerald-600">
                                    Approve &amp; Confirm Order
                                </button>
                                <button type="submit" name="decision" value="reject"
                                        class="flex-1 rounded-lg bg-[#D97706] px-3 py-2 text-xs font-bold text-white hover:bg-amber-700">
                                    Reject Payment
                                </button>
                            </div>
                        </form>
                    @endif
                </div>

                @if($order->isCancellable())
                    <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-sm font-bold text-[#DC2626]">Batalkan Order</h3>
                        <form method="POST" action="{{ $cancelUrl }}" class="space-y-3">
                            @csrf
                            <input type="text" name="reason" required minlength="3" placeholder="Alasan pembatalan..."
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-xs text-[#142B4A]" />
                            <button type="submit"
                                    class="w-full rounded-lg bg-[#DC2626] px-3 py-2 text-xs font-bold text-white hover:bg-red-700">
                                Batalkan Order &amp; Release Stok
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
