@php
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : url('/shop');
    $registerStoreUrl = \Illuminate\Support\Facades\Route::has('shop.register.store') ? route('shop.register.store') : url('/shop/register');
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
@endphp

<x-guest-layout title="Daftar Akun Customer">
    <div class="min-h-screen bg-[#F8FAFC] flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-md space-y-6">
            <div class="text-center">
                <a href="{{ $shopUrl }}" class="inline-flex items-center gap-3">
                    <x-application-logo class="h-11 w-11" />
                    <span class="text-xl font-extrabold text-[#142B4A]">{{ brand_name() }}</span>
                </a>
                <h1 class="mt-4 text-2xl font-bold text-[#142B4A]">Daftar Akun Pelanggan</h1>
                <p class="mt-1 text-sm text-slate-500">Buat akun untuk memesan produk, unggah bukti bayar, dan pantau pengiriman.</p>
            </div>

            <div class="rounded-2xl border border-[#DCE3EA] bg-white p-6 shadow-sm">
                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-[#DC2626]">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $registerStoreUrl }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-semibold text-[#142B4A] mb-1">Nama Lengkap</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-[#142B4A] mb-1">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="username" class="block text-xs font-semibold text-[#142B4A] mb-1">Username (Opsional)</label>
                            <input id="username" type="text" name="username" value="{{ old('username') }}"
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-[#142B4A] mb-1">No. Telepon / WA</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required
                                   placeholder="08xxxxxxxxxx"
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-[#142B4A] mb-1">Password</label>
                        <input id="password" type="password" name="password" required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-[#142B4A] mb-1">Konfirmasi Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-[#2563EB] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1D4ED8] transition-colors">
                        Daftar &amp; Mulai Belanja
                    </button>
                </form>

                <div class="mt-4 border-t border-[#DCE3EA] pt-4 text-center text-xs text-slate-600">
                    Sudah punya akun?
                    <a href="{{ $loginUrl }}" class="font-semibold text-[#2563EB] hover:underline">Masuk di sini</a>
                    &middot;
                    <a href="{{ $shopUrl }}" class="font-semibold text-[#0F766E] hover:underline">Lihat Katalog</a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
