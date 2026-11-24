@php
    $myOrdersUrl = \Illuminate\Support\Facades\Route::has('my-orders.index') ? route('my-orders.index') : url('/my-orders');
    $uploadProofUrl = \Illuminate\Support\Facades\Route::has('my-orders.upload-proof') ? route('my-orders.upload-proof', $order) : url('/my-orders/' . $order->id . '/proof');
    $cancelUrl = \Illuminate\Support\Facades\Route::has('my-orders.cancel') ? route('my-orders.cancel', $order) : url('/my-orders/' . $order->id . '/cancel');
@endphp

<x-guest-layout title="Detail Pesanan #{{ $order->order_number }}">
    <div class="min-h-screen bg-[#F8FAFC] text-[#142B4A]">
        <header class="border-b border-[#DCE3EA] bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
                <a href="{{ $myOrdersUrl }}" class="flex items-center gap-3">
                    <x-application-logo class="h-9 w-9" />
                    <div>
                        <p class="text-sm font-bold text-[#142B4A]">Pesanan #{{ $order->order_number }}</p>
                        <p class="text-[11px] text-slate-500">{{ brand_name() }}</p>
                    </div>
                </a>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $myOrdersUrl }}"
                       class="rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-xs font-semibold text-[#142B4A] hover:bg-[#F8FAFC]">
                        &larr; Daftar Pesanan
                    </a>
                    <a href="{{ $trackingUrl }}" target="_blank"
                       class="rounded-lg bg-[#0F766E] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#0D655E]">
                        Lacak Publik
                    </a>
                    @if($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                           class="rounded-lg bg-[#10B981] px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-600">
                            Chat Admin WA
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">
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
            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-[#DC2626]">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Status Overview --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status Pesanan</p>
                    <span class="mt-2 inline-block rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $order->statusBadgeClasses() }}">
                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pembayaran</p>
                    <span class="mt-2 inline-block rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $order->paymentBadgeClasses() }}">
                        {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                    </span>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pengiriman</p>
                    <p class="mt-2 text-sm font-bold text-[#142B4A]">{{ strtoupper(str_replace('_', ' ', $order->shipment_status)) }}</p>
                </div>
                <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Tagihan</p>
                    <p class="mt-1.5 text-xl font-extrabold text-[#142B4A]">{{ format_money($order->total) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                {{-- Items & History --}}
                <div class="lg:col-span-7 space-y-6">
                    <div class="rounded-2xl border border-[#DCE3EA] bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-[#DCE3EA] px-5 py-3.5">
                            <h2 class="text-sm font-bold text-[#142B4A]">Item Pesanan</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[#F8FAFC] text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3">Produk</th>
                                        <th class="px-4 py-3 text-center">Qty</th>
                                        <th class="px-4 py-3 text-right">Harga</th>
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

                    {{-- Status Timeline --}}
                    <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-bold text-[#142B4A]">Riwayat Status</h2>
                        <div class="mt-4 space-y-3">
                            @foreach($order->statusHistories as $history)
                                <div class="flex items-start gap-3 border-l-2 border-[#2563EB] pl-3">
                                    <div>
                                        <p class="text-xs font-bold text-[#142B4A]">
                                            {{ strtoupper(str_replace('_', ' ', $history->to_status)) }}
                                        </p>
                                        <p class="text-xs text-slate-600">{{ $history->reason ?: 'Status diperbarui' }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $history->created_at?->format('d M Y H:i') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Actions: Payment Proof, Address, Cancel --}}
                <div class="lg:col-span-5 space-y-6">
                    @if($order->address)
                        <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
                            <h2 class="text-sm font-bold text-[#142B4A]">Alamat Pengiriman</h2>
                            <p class="mt-2 text-sm font-semibold text-[#142B4A]">{{ $order->address->recipient_name }} ({{ $order->address->phone }})</p>
                            <p class="mt-1 text-xs text-slate-600">{{ $order->address->formattedAddress() }}</p>
                        </div>
                    @endif

                    @if($order->status !== 'cancelled' && $order->payment_status !== 'paid')
                        @php($bankInfo = payment_bank_info())
                        <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm space-y-3">
                            <h2 class="text-sm font-bold text-[#142B4A]">Pembayaran &amp; Konfirmasi</h2>

                            @if($bankInfo['account_number'] !== '')
                                <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-3 text-xs text-[#142B4A] space-y-0.5">
                                    <p class="font-bold">Rekening Tujuan Transfer</p>
                                    <p>Bank: <strong>{{ $bankInfo['bank_name'] ?: '-' }}</strong></p>
                                    <p>No. Rekening: <strong class="font-mono text-sm">{{ $bankInfo['account_number'] }}</strong></p>
                                    <p>Atas Nama: <strong>{{ $bankInfo['account_holder'] ?: brand_name() }}</strong></p>
                                </div>
                            @endif

                            <p class="text-xs text-slate-500">Unggah bukti transfer (JPG, PNG, WEBP, atau PDF, maks. 4 MB) atau konfirmasi ke admin.</p>

                            @if($order->latestPayment?->proof_path)
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-[#2563EB]">
                                    Bukti terakhir sudah diunggah (status: <strong>{{ strtoupper($order->latestPayment->status) }}</strong>). Anda dapat mengganti dengan unggahan baru.
                                </div>
                            @endif

                            <form method="POST" action="{{ $uploadProofUrl }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <input type="file" name="proof" required accept=".jpg,.jpeg,.png,.webp,.pdf"
                                       class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#2563EB] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-[#1D4ED8]" />
                                <button type="submit"
                                        class="w-full rounded-xl bg-[#2563EB] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#1D4ED8]">
                                    Kirim Bukti Pembayaran
                                </button>
                            </form>

                            @if($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="block w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-center text-xs font-bold text-[#0F766E] hover:bg-emerald-100">
                                    Konfirmasi Pembayaran via WA Admin
                                </a>
                            @endif
                        </div>
                    @endif

                    @if($order->isCancellable())
                        <div class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm space-y-3">
                            <h2 class="text-sm font-bold text-[#DC2626]">Batalkan Pesanan</h2>
                            <form method="POST" action="{{ $cancelUrl }}" class="space-y-3">
                                @csrf
                                <input type="text" name="reason" required minlength="3" placeholder="Alasan pembatalan..."
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-xs text-[#142B4A]" />
                                <button type="submit"
                                        class="w-full rounded-xl bg-[#DC2626] px-4 py-2 text-xs font-bold text-white hover:bg-red-700">
                                    Batalkan Pesanan
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</x-guest-layout>
