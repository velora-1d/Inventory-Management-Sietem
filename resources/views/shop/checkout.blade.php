@php
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : url('/shop');
    $myOrdersUrl = \Illuminate\Support\Facades\Route::has('my-orders.index') ? route('my-orders.index') : url('/my-orders');
    $placeOrderUrl = \Illuminate\Support\Facades\Route::has('shop.place-order') ? route('shop.place-order') : url('/shop/checkout');
@endphp

<x-guest-layout title="Checkout Pesanan">
    <div class="min-h-screen bg-[#F8FAFC] text-[#142B4A]" x-data="{ deliveryType: '{{ old('delivery_type', 'delivery') }}', selectedAddress: '{{ old('customer_address_id', $addresses->firstWhere('is_default', true)?->id ?? ($addresses->first()?->id ?? '')) }}' }">
        <header class="border-b border-[#DCE3EA] bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
                <a href="{{ $shopUrl }}" class="flex items-center gap-3">
                    <x-application-logo class="h-9 w-9" />
                    <div>
                        <p class="text-sm font-bold text-[#142B4A]">{{ brand_name() }}</p>
                        <p class="text-[11px] text-slate-500">Checkout Pesanan</p>
                    </div>
                </a>
                <div class="flex items-center gap-2.5">
                    <a href="{{ $shopUrl }}"
                       class="rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-xs font-semibold text-[#142B4A] hover:bg-[#F8FAFC]">
                        &larr; Kembali ke Katalog
                    </a>
                    @if(auth()->check())
                        <a href="{{ $myOrdersUrl }}"
                           class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-xs font-semibold text-[#142B4A] hover:bg-slate-100">
                            Pesanan Saya
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">
            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-[#DC2626]">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#DC2626]">
                    <p class="font-semibold">Periksa kembali data checkout:</p>
                    <ul class="mt-1 list-disc list-inside text-xs space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $placeOrderUrl }}" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                @csrf

                <div class="lg:col-span-7 space-y-6">
                    {{-- Delivery Method --}}
                    <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-[#142B4A]">1. Metode Pengambilan / Pengiriman</h2>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition-colors"
                                   :class="deliveryType === 'delivery' ? 'border-[#2563EB] bg-blue-50/40' : 'border-[#DCE3EA] bg-[#F8FAFC]'">
                                <input type="radio" name="delivery_type" value="delivery" x-model="deliveryType" class="mt-1 text-[#2563EB] focus:ring-[#2563EB]" />
                                <div>
                                    <p class="text-sm font-bold text-[#142B4A]">Dikirim ke Alamat</p>
                                    <p class="text-xs text-slate-500">Pengiriman melalui kurir ke alamat tujuan.</p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition-colors"
                                   :class="deliveryType === 'pickup' ? 'border-[#2563EB] bg-blue-50/40' : 'border-[#DCE3EA] bg-[#F8FAFC]'">
                                <input type="radio" name="delivery_type" value="pickup" x-model="deliveryType" class="mt-1 text-[#2563EB] focus:ring-[#2563EB]" />
                                <div>
                                    <p class="text-sm font-bold text-[#142B4A]">Ambil di Toko (Pickup)</p>
                                    <p class="text-xs text-slate-500">Ambil langsung di lokasi setelah pesanan siap.</p>
                                </div>
                            </label>
                        </div>

                        {{-- Shipping Address --}}
                        <div x-show="deliveryType === 'delivery'" class="space-y-4 border-t border-[#DCE3EA] pt-4">
                            @if($addresses->isNotEmpty())
                                <div>
                                    <label for="customer_address_id" class="block text-xs font-semibold text-[#142B4A] mb-1.5">
                                        Pilih Alamat Tersimpan
                                    </label>
                                    <select id="customer_address_id"
                                            name="customer_address_id"
                                            x-model="selectedAddress"
                                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]">
                                        <option value="">+ Gunakan alamat baru</option>
                                        @foreach($addresses as $addr)
                                            <option value="{{ $addr->id }}">
                                                {{ $addr->label }} — {{ $addr->recipient_name }} ({{ $addr->phone }}) — {{ $addr->formattedAddress() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="customer_address_id" value="" />
                            @endif

                            <div x-show="!selectedAddress" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Label Alamat</label>
                                    <input type="text" name="address_label" value="{{ old('address_label', 'Rumah') }}"
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Nama Penerima</label>
                                    <input type="text" name="recipient_name" value="{{ old('recipient_name', auth()->user()?->name) }}"
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Nomor Telepon / WhatsApp</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}"
                                           placeholder="08xxxxxxxxxx"
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Kota / Kabupaten</label>
                                    <input type="text" name="city" value="{{ old('city') }}"
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Provinsi</label>
                                    <input type="text" name="province" value="{{ old('province') }}"
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Kode Pos</label>
                                    <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Alamat Lengkap</label>
                                    <textarea name="address_line" rows="2"
                                              class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">{{ old('address_line') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-[#142B4A]">2. Metode Pembayaran</h2>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-3">
                                <input type="radio" name="payment_method" value="manual_transfer" @checked(old('payment_method', 'manual_transfer') === 'manual_transfer') class="mt-1 text-[#2563EB]" />
                                <div>
                                    <p class="text-sm font-bold text-[#142B4A]">Transfer Bank (Manual)</p>
                                    <p class="text-xs text-slate-500">Unggah bukti transfer di halaman detail pesanan.</p>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-[#DCE3EA] bg-[#F8FAFC] p-3">
                                <input type="radio" name="payment_method" value="qris" @checked(old('payment_method') === 'qris') class="mt-1 text-[#2563EB]" />
                                <div>
                                    <p class="text-sm font-bold text-[#142B4A]">QRIS</p>
                                    <p class="text-xs text-slate-500">Pembayaran QRIS via gateway / verifikasi manual.</p>
                                </div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="gateway" class="block text-xs font-semibold text-[#142B4A] mb-1">Payment Gateway</label>
                                <select id="gateway" name="gateway"
                                        class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                                    @foreach($gateways as $key => $gw)
                                        <option value="{{ $key }}" @selected(old('gateway', $activeGateway) === $key)>
                                            {{ $gw['label'] }} {{ $gw['configured'] ? '(Aktif)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="notes" class="block text-xs font-semibold text-[#142B4A] mb-1">Catatan Pesanan (Opsional)</label>
                                <input id="notes" type="text" name="notes" value="{{ old('notes') }}"
                                       placeholder="Contoh: Kirim siang hari"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order Summary --}}
                <div class="lg:col-span-5">
                    <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm space-y-4">
                        <h2 class="text-base font-bold text-[#142B4A]">Ringkasan Pesanan</h2>
                        <div class="divide-y divide-[#DCE3EA]">
                            @foreach($cartItems as $item)
                                <div class="flex items-center justify-between py-2.5 text-sm">
                                    <div>
                                        <p class="font-semibold text-[#142B4A]">{{ $item['product']->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $item['quantity'] }} x {{ format_money($item['product']->selling_price) }}</p>
                                    </div>
                                    <span class="font-bold text-[#142B4A]">{{ format_money($item['subtotal']) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-[#DCE3EA] pt-3 space-y-2 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal ({{ $cartCount }} item)</span>
                                <span>{{ format_money($cartSubtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Biaya Pengiriman</span>
                                <span>{{ format_money(0) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-[#DCE3EA] pt-2 text-base font-extrabold text-[#142B4A]">
                                <span>Total Pembayaran</span>
                                <span>{{ format_money($cartSubtotal) }}</span>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full rounded-xl bg-[#2563EB] px-4 py-3 text-sm font-bold text-white hover:bg-[#1D4ED8] transition-colors">
                            Buat Pesanan Sekarang
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</x-guest-layout>
