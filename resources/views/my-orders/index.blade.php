@php
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : url('/shop');
    $myOrdersUrl = \Illuminate\Support\Facades\Route::has('my-orders.index') ? route('my-orders.index') : url('/my-orders');
    $storeAddressUrl = \Illuminate\Support\Facades\Route::has('my-orders.addresses.store') ? route('my-orders.addresses.store') : url('/my-orders/addresses');
@endphp

<x-guest-layout title="Pesanan Saya">
    <div class="min-h-screen bg-[#F8FAFC] text-[#142B4A]">
        <header class="border-b border-[#DCE3EA] bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
                <a href="{{ $shopUrl }}" class="flex items-center gap-3">
                    <x-application-logo class="h-9 w-9" />
                    <div>
                        <p class="text-sm font-bold text-[#142B4A]">{{ brand_name() }}</p>
                        <p class="text-[11px] text-slate-500">Portal Pelanggan &mdash; {{ auth()->user()?->name }}</p>
                    </div>
                </a>

                <div class="flex items-center gap-2.5">
                    <a href="{{ $shopUrl }}"
                       class="rounded-lg bg-[#2563EB] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#1D4ED8]">
                        + Belanja Lagi
                    </a>
                    @if(Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:text-[#DC2626]">
                                Keluar
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">
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

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                {{-- Order History --}}
                <div class="lg:col-span-8 space-y-4">
                    <div class="rounded-2xl border border-[#DCE3EA] bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-[#DCE3EA] px-5 py-4">
                            <h1 class="text-lg font-bold text-[#142B4A]">Riwayat Pesanan Saya</h1>
                            <p class="text-xs text-slate-500">Pantau status pembayaran, proses penyiapan, dan pengiriman pesanan Anda.</p>
                        </div>

                        @if($orders->isEmpty())
                            <div class="p-10 text-center">
                                <p class="text-sm font-semibold text-[#142B4A]">Belum ada pesanan</p>
                                <p class="mt-1 text-xs text-slate-500">Mulai belanja melalui katalog produk kami.</p>
                                <a href="{{ $shopUrl }}"
                                   class="mt-4 inline-flex rounded-lg bg-[#2563EB] px-4 py-2 text-xs font-semibold text-white hover:bg-[#1D4ED8]">
                                    Buka Katalog
                                </a>
                            </div>
                        @else
                            <div class="divide-y divide-[#DCE3EA]">
                                @foreach($orders as $order)
                                    @php
                                        $detailUrl = \Illuminate\Support\Facades\Route::has('my-orders.show')
                                            ? route('my-orders.show', $order)
                                            : url('/my-orders/' . $order->id);
                                    @endphp
                                    <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-bold text-[#142B4A]">#{{ $order->order_number }}</span>
                                                <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $order->statusBadgeClasses() }}">
                                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                                </span>
                                                <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $order->paymentBadgeClasses() }}">
                                                    {{ strtoupper(str_replace('_', ' ', $order->payment_status)) }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-500">
                                                {{ $order->created_at?->format('d M Y H:i') }}
                                                &middot; {{ $order->items->count() }} produk
                                                &middot; {{ strtoupper($order->delivery_type) }}
                                            </p>
                                        </div>

                                        <div class="flex items-center justify-between gap-4 sm:justify-end">
                                            <p class="text-base font-extrabold text-[#142B4A]">{{ format_money($order->total) }}</p>
                                            <a href="{{ $detailUrl }}"
                                               class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3.5 py-2 text-xs font-semibold text-[#2563EB] hover:bg-blue-50">
                                                Detail &amp; Bayar
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-[#DCE3EA] px-5 py-3">
                                {{ $orders->links() }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Saved Addresses --}}
                <div class="lg:col-span-4 space-y-4">
                    <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-[#142B4A]">Buku Alamat Saya</h2>

                        @if($addresses->isEmpty())
                            <p class="text-xs text-slate-500">Belum ada alamat tersimpan.</p>
                        @else
                            <div class="space-y-2.5">
                                @foreach($addresses as $address)
                                    <div class="rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-[#142B4A]">{{ $address->label }}</span>
                                            @if($address->is_default)
                                                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-[#10B981]">
                                                    Utama
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs font-semibold text-slate-700">{{ $address->recipient_name }} ({{ $address->phone }})</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $address->formattedAddress() }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ $storeAddressUrl }}" class="border-t border-[#DCE3EA] pt-4 space-y-3">
                            @csrf
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Tambah Alamat Baru</p>
                            <input type="text" name="label" placeholder="Label (Rumah / Kantor)" required
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-1.5 text-xs text-[#142B4A]" />
                            <input type="text" name="recipient_name" placeholder="Nama Penerima" required
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-1.5 text-xs text-[#142B4A]" />
                            <input type="text" name="phone" placeholder="No. WhatsApp" required
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-1.5 text-xs text-[#142B4A]" />
                            <textarea name="address_line" rows="2" placeholder="Alamat Lengkap" required
                                      class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-1.5 text-xs text-[#142B4A]"></textarea>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="city" placeholder="Kota" required
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-1.5 text-xs text-[#142B4A]" />
                                <input type="text" name="postal_code" placeholder="Kode Pos"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-1.5 text-xs text-[#142B4A]" />
                            </div>
                            <label class="flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="is_default" value="1" class="rounded border-[#DCE3EA] text-[#2563EB]" />
                                Jadikan alamat utama
                            </label>
                            <button type="submit"
                                    class="w-full rounded-lg bg-[#142B4A] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1E3A5F]">
                                Simpan Alamat
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-guest-layout>
