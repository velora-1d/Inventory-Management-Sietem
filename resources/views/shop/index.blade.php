@php
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : url('/shop');
    $checkoutUrl = \Illuminate\Support\Facades\Route::has('shop.checkout') ? route('shop.checkout') : url('/shop/checkout');
    $myOrdersUrl = \Illuminate\Support\Facades\Route::has('my-orders.index') ? route('my-orders.index') : url('/my-orders');
    $registerUrl = \Illuminate\Support\Facades\Route::has('shop.register') ? route('shop.register') : url('/shop/register');
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
@endphp

<x-guest-layout title="Katalog Belanja">
    <div class="min-h-screen bg-[#F8FAFC] text-[#142B4A]">
        {{-- Top Blueprint Header --}}
        <header class="sticky top-0 z-30 border-b border-[#DCE3EA] bg-white/95 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2.5 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ $shopUrl }}" wire:navigate class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                    <x-application-logo class="h-8 w-8 shrink-0 sm:h-9 sm:w-9" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold tracking-tight text-[#142B4A]">{{ brand_name() }}</p>
                        <p class="hidden text-[11px] font-medium text-slate-500 sm:block">Customer Storefront &amp; Order Portal</p>
                    </div>
                </a>

                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <a href="{{ $checkoutUrl }}" wire:navigate
                       class="inline-flex items-center gap-1.5 rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#142B4A] hover:border-[#2563EB] hover:text-[#2563EB] transition-colors sm:gap-2 sm:px-3 sm:py-2">
                        <span>Keranjang</span>
                        <span class="rounded-full bg-[#2563EB] px-2 py-0.5 text-[11px] font-bold text-white">{{ $cartCount }}</span>
                    </a>

                    @if(auth()->check())
                        <a href="{{ $myOrdersUrl }}" wire:navigate
                           class="inline-flex items-center rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-xs font-semibold text-[#142B4A] hover:bg-slate-100 transition-colors sm:px-3 sm:py-2">
                            Pesanan Saya
                        </a>
                        @if(Route::has('logout'))
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-[#DC2626] transition-colors sm:px-3 sm:py-2">
                                    Keluar
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ $loginUrl }}" wire:navigate
                           class="inline-flex items-center rounded-lg border border-[#DCE3EA] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#142B4A] hover:bg-[#F8FAFC] transition-colors sm:px-3 sm:py-2">
                            Masuk
                        </a>
                        <a href="{{ $registerUrl }}" wire:navigate
                           class="inline-flex items-center rounded-lg bg-[#2563EB] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1D4ED8] transition-colors sm:px-3.5 sm:py-2">
                            Daftar
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-5 pb-24 sm:px-6 sm:py-6 lg:px-8 lg:pb-8 space-y-5 sm:space-y-6">
            {{-- Flash Messages --}}
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

            {{-- Hero / Filter Bar --}}
            <div class="rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-[#2563EB]">
                            Katalog Produk
                        </span>
                        <h1 class="mt-2 text-2xl font-bold text-[#142B4A]">Pesan Produk Secara Langsung</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Pilih produk dengan stok real-time, atur keranjang, lalu lanjutkan ke checkout dan pelacakan pesanan.
                        </p>
                    </div>

                    <form method="GET" action="{{ $shopUrl }}" class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Cari nama produk atau SKU..."
                               class="w-full sm:w-64 rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3.5 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />

                        <select name="category_id"
                                class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected($selectedCategoryId === $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                                class="rounded-lg bg-[#142B4A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1E3A5F] transition-colors">
                            Filter
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                {{-- Product Grid --}}
                <div class="lg:col-span-8 space-y-4">
                    @if($products->isEmpty())
                        <div class="rounded-2xl border border-[#DCE3EA] bg-white p-10 text-center shadow-sm">
                            <p class="text-base font-semibold text-[#142B4A]">Produk tidak ditemukan</p>
                            <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($products as $product)
                                @php
                                    $available = (int) ($product->available_stock ?? 0);
                                    $imageSrc = null;
                                    if (!empty($product->image)) {
                                        $imageSrc = \Illuminate\Support\Str::startsWith($product->image, ['http://', 'https://', '/'])
                                            ? $product->image
                                            : asset('storage/' . ltrim($product->image, '/'));
                                    }
                                @endphp
                                <div class="flex flex-col justify-between rounded-2xl border border-[#DCE3EA] bg-white p-4 shadow-sm transition-shadow hover:shadow-md">
                                    <div>
                                        <div class="relative mb-3 h-44 w-full overflow-hidden rounded-xl border border-[#DCE3EA] bg-gradient-to-br from-slate-50 via-blue-50/60 to-sky-50">
                                            @if($imageSrc)
                                                <img src="{{ $imageSrc }}"
                                                     alt="{{ $product->name }}"
                                                     loading="lazy"
                                                     class="h-full w-full object-cover transition-transform duration-300 hover:scale-105" />
                                            @else
                                                <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-slate-400">
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/90 text-[#2563EB] shadow-sm border border-[#DCE3EA]">
                                                        <x-heroicon-o-cube class="h-6 w-6" />
                                                    </div>
                                                    <span class="text-[11px] font-medium text-slate-500">
                                                        {{ $product->category?->name ?? 'Produk' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex items-start justify-between gap-2">
                                            <span class="rounded-md border border-[#DCE3EA] bg-[#F8FAFC] px-2 py-0.5 text-[11px] font-mono text-slate-600">
                                                {{ $product->sku ?: 'SKU-' . $product->id }}
                                            </span>
                                            @if($available > 0)
                                                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-[#10B981]">
                                                    Stok: {{ $available }}
                                                </span>
                                            @else
                                                <span class="rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-[#DC2626]">
                                                    Habis
                                                </span>
                                            @endif
                                        </div>

                                        <h2 class="mt-3 text-base font-bold text-[#142B4A] line-clamp-2">{{ $product->name }}</h2>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $product->category?->name ?? 'Umum' }}
                                            @if($product->unit)
                                                &middot; {{ $product->unit->name }}
                                            @endif
                                        </p>

                                        @if($product->description)
                                            <p class="mt-2 text-xs text-slate-600 line-clamp-2">{{ $product->description }}</p>
                                        @endif
                                    </div>

                                    <div class="mt-4 border-t border-[#DCE3EA] pt-3">
                                        <p class="text-lg font-extrabold text-[#142B4A]">{{ format_money($product->selling_price) }}</p>

                                        @if($available > 0)
                                            <form method="POST" action="{{ route('shop.cart.add', $product) }}" class="mt-2.5 flex items-center gap-2">
                                                @csrf
                                                <input type="number"
                                                       name="quantity"
                                                       value="1"
                                                       min="1"
                                                       max="{{ $available }}"
                                                       class="w-20 rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                                <button type="submit"
                                                        class="flex-1 rounded-lg bg-[#2563EB] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1D4ED8] transition-colors">
                                                    + Keranjang
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" disabled
                                                    class="mt-2.5 w-full cursor-not-allowed rounded-lg border border-[#DCE3EA] bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-400">
                                                Stok Kosong
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-2 overflow-x-auto" id="shop-pagination">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>

                {{-- Session Cart Sidebar --}}
                <aside id="shop-cart" class="lg:col-span-4">
                    <div class="sticky top-20 rounded-2xl border border-[#DCE3EA] bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3">
                            <h2 class="text-base font-bold text-[#142B4A]">Keranjang Belanja</h2>
                            <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-[#2563EB]">
                                {{ $cartCount }} item
                            </span>
                        </div>

                        @if(empty($cartItems))
                            <div class="py-8 text-center">
                                <p class="text-sm font-medium text-slate-600">Keranjang masih kosong</p>
                                <p class="mt-1 text-xs text-slate-400">Tambahkan produk dari katalog untuk mulai checkout.</p>
                            </div>
                        @else
                            <div class="divide-y divide-[#DCE3EA]">
                                @foreach($cartItems as $item)
                                    <div class="py-3.5 space-y-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-semibold text-[#142B4A]">{{ $item['product']->name }}</p>
                                                <p class="text-xs text-slate-500">{{ format_money($item['product']->selling_price) }} / unit</p>
                                            </div>
                                            <p class="text-sm font-bold text-[#142B4A]">{{ format_money($item['subtotal']) }}</p>
                                        </div>

                                        <div class="flex items-center justify-between gap-2">
                                            <form method="POST" action="{{ route('shop.cart.update', $item['product']) }}" class="flex items-center gap-1.5">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number"
                                                       name="quantity"
                                                       value="{{ $item['quantity'] }}"
                                                       min="0"
                                                       max="{{ $item['available'] }}"
                                                       class="w-16 rounded-md border border-[#DCE3EA] bg-[#F8FAFC] px-2 py-1 text-xs text-[#142B4A]" />
                                                <button type="submit"
                                                        class="rounded-md border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1 text-xs font-semibold text-[#142B4A] hover:bg-slate-100">
                                                    Update
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('shop.cart.remove', $item['product']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-xs font-semibold text-[#DC2626] hover:underline">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 border-t border-[#DCE3EA] pt-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-600">Subtotal</span>
                                    <span class="text-lg font-extrabold text-[#142B4A]">{{ format_money($cartSubtotal) }}</span>
                                </div>

                                <a href="{{ $checkoutUrl }}" wire:navigate
                                   class="block w-full rounded-xl bg-[#0F766E] px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-[#0D655E] transition-colors">
                                    Lanjut ke Checkout
                                </a>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </main>

        {{-- Mobile Sticky Cart Bar --}}
        @if($cartCount > 0)
            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-[#DCE3EA] bg-white/95 px-4 py-3 backdrop-blur-md shadow-lg lg:hidden">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium text-slate-500">{{ $cartCount }} item di keranjang</p>
                        <p class="truncate text-sm font-extrabold text-[#142B4A]">{{ format_money($cartSubtotal) }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="#shop-cart"
                           class="rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-xs font-semibold text-[#142B4A]">
                            Keranjang
                        </a>
                        <a href="{{ $checkoutUrl }}" wire:navigate
                           class="rounded-lg bg-[#0F766E] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#0D655E]">
                            Checkout
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        (function () {
            if (window.__shopPaginationBound) return;
            window.__shopPaginationBound = true;

            document.addEventListener('click', function (event) {
                const link = event.target.closest('#shop-pagination a[href]');
                if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                    event.preventDefault();
                    window.Livewire.navigate(link.href);
                }
            });
        })();
    </script>
</x-guest-layout>
