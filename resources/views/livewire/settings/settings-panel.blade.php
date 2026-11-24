<div class="space-y-6">
    {{-- Top Tab Bar --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-2 shadow-sm backdrop-blur-md">
        <nav class="flex flex-wrap gap-1.5" aria-label="Settings Tabs">
            <button type="button"
                    wire:click="setTab('branding')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all {{ $activeTab === 'branding' ? 'bg-sky-600 text-white shadow-sm shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <x-heroicon-o-building-storefront class="h-4 w-4" />
                <span>Toko &amp; Branding</span>
            </button>

            <button type="button"
                    wire:click="setTab('payment')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all {{ $activeTab === 'payment' ? 'bg-sky-600 text-white shadow-sm shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <x-heroicon-o-credit-card class="h-4 w-4" />
                <span>Pembayaran &amp; Rekening</span>
            </button>

            <button type="button"
                    wire:click="setTab('currency')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all {{ $activeTab === 'currency' ? 'bg-sky-600 text-white shadow-sm shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <x-heroicon-o-banknotes class="h-4 w-4" />
                <span>Mata Uang &amp; Saldo</span>
            </button>

            <button type="button"
                    wire:click="setTab('advanced')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all {{ $activeTab === 'advanced' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <x-heroicon-o-table-cells class="h-4 w-4" />
                <span>Semua Key (Lanjutan)</span>
            </button>
        </nav>
    </div>

    {{-- TAB 1: TOKO & BRANDING --}}
    @if($activeTab === 'branding')
        <form wire:submit="saveBranding" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8 space-y-6">
                {{-- Brand Identity --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-base font-bold text-slate-800">Identitas Brand &amp; Aplikasi</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Tampil di sidebar admin, halaman login, dan portal belanja customer.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Brand / Aplikasi</label>
                            <input type="text" wire:model.live="brand_name"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                            <x-input-error :messages="$errors->get('brand_name')" class="mt-1" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subtitle Brand</label>
                            <input type="text" wire:model.live="brand_subtitle"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kode Singkat (Short Code)</label>
                            <input type="text" wire:model.live="brand_short_code" maxlength="10"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm font-mono text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>
                    </div>

                    {{-- Logo Upload --}}
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/70 p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
                                @if($logoUpload)
                                    <img src="{{ $logoUpload->temporaryUrl() }}" alt="Preview" class="h-full w-full object-contain" />
                                @elseif($brand_logo)
                                    <img src="{{ brand_logo_url() }}" alt="Current Logo" class="h-full w-full object-contain" />
                                @else
                                    <x-application-logo class="h-11 w-11" />
                                @endif
                            </div>

                            <div class="flex-1 space-y-2">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Logo Aplikasi</p>
                                    <p class="text-[11px] text-slate-500">PNG, JPG, WebP, atau SVG (maks. 2 MB).</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <input type="file" wire:model="logoUpload" accept=".png,.jpg,.jpeg,.webp,.svg"
                                           class="block text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-sky-600 file:px-3 py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-sky-700" />
                                    @if($brand_logo)
                                        <button type="button" wire:click="resetLogo"
                                                class="text-xs font-semibold text-red-600 hover:underline">
                                            Reset ke Default SVG
                                        </button>
                                    @endif
                                </div>
                                <x-input-error :messages="$errors->get('logoUpload')" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Store & WhatsApp --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-base font-bold text-slate-800">Kontak Toko &amp; WhatsApp Admin</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Nomor WhatsApp Admin dipakai untuk tombol konfirmasi pesanan di landing page `/shop`.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">WhatsApp Admin</label>
                            <input type="text" wire:model.live="brand_whatsapp_admin" placeholder="081234567890"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm font-mono text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Telepon Toko (Struk)</label>
                            <input type="text" wire:model="store_phone" placeholder="081234567890"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm font-mono text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Toko Resmi</label>
                            <input type="text" wire:model="store_name"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Toko</label>
                            <textarea wire:model="store_address" rows="2"
                                      class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-sky-700 transition-colors">
                            <x-heroicon-o-check class="h-4 w-4" />
                            <span>Simpan Toko &amp; Branding</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Live Preview Sidebar --}}
            <div class="lg:col-span-4 space-y-4">
                <div class="sticky top-20 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Live Preview</p>

                    <div class="rounded-xl bg-[#142B4A] p-4 text-white flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 overflow-hidden">
                            @if($logoUpload)
                                <img src="{{ $logoUpload->temporaryUrl() }}" alt="Preview" class="h-8 w-8 object-contain" />
                            @elseif($brand_logo)
                                <img src="{{ brand_logo_url() }}" alt="Logo" class="h-8 w-8 object-contain" />
                            @else
                                <x-application-logo class="h-7 w-7" />
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold">{{ $brand_name ?: 'Nama Brand' }}</p>
                            <p class="truncate text-[11px] text-sky-200/80">{{ $brand_subtitle ?: 'Subtitle Brand' }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 space-y-1">
                        <p class="text-[11px] font-bold text-emerald-800">Tombol WA di Landing Page</p>
                        <p class="text-xs text-emerald-700 font-mono">{{ $brand_whatsapp_admin ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </form>
    @endif

    {{-- TAB 2: PEMBAYARAN & REKENING BANK --}}
    @if($activeTab === 'payment')
        <form wire:submit="savePayment" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8 space-y-6">
                {{-- Gateway Selector --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-base font-bold text-slate-800">Payment Gateway Aktif</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Pilih gateway untuk pembayaran online (QRIS, E-Wallet, Virtual Account) di POS dan `/shop`.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($gateways as $key => $gw)
                            <label class="relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all {{ $payment_gateway === $key ? 'border-sky-600 bg-sky-50/50 ring-2 ring-sky-500/20' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300' }}">
                                <input type="radio" wire:model.live="payment_gateway" value="{{ $key }}" class="sr-only" />
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-sm font-bold text-slate-800">{{ $gw['label'] }}</span>
                                    @if($gw['configured'])
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Siap</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Perlu Config</span>
                                    @endif
                                </div>
                                <span class="mt-2 text-[11px] font-mono text-slate-500">{{ $key }}</span>
                            </label>
                        @endforeach
                    </div>

                    {{-- Gateway Methods --}}
                    <div class="pt-2">
                        <p class="text-xs font-semibold text-slate-700 mb-2">Metode yang Diarahkan ke Gateway Online</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['qris' => 'QRIS', 'ewallet' => 'E-Wallet', 'virtual_account' => 'Virtual Account', 'transfer' => 'Transfer Online'] as $mKey => $mLabel)
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition-colors {{ in_array($mKey, $gatewayMethods, true) ? 'border-sky-600 bg-sky-50 text-sky-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                                    <input type="checkbox" wire:model.live="gatewayMethods" value="{{ $mKey }}" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    <span>{{ $mLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Gateway-specific credentials --}}
                    @if($payment_gateway === 'verapay')
                        <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4 space-y-3">
                            <p class="text-xs font-bold text-sky-900">Konfigurasi VeraPay Payment Hub (kosongkan jika pakai `.env`)</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Base URL</label>
                                    <input type="text" wire:model="payment_verapay_base_url" placeholder="https://verapay.digital"
                                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Project Slug</label>
                                    <input type="text" wire:model="payment_verapay_slug" placeholder="blash"
                                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">API Key</label>
                                    <input type="password" wire:model="payment_verapay_api_key" autocomplete="new-password" placeholder="Kosongkan untuk tetap memakai VERAPAY_API_KEY di .env"
                                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800" />
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-600">
                                Webhook URL: <code class="rounded bg-white px-1.5 py-0.5 font-mono text-[11px] text-sky-700">{{ url('/api/v1/webhooks/payment/verapay') }}</code>
                            </p>
                        </div>
                    @elseif($payment_gateway === 'pakasir')
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                            <p class="text-xs font-bold text-slate-800">Konfigurasi Pakasir</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Base URL</label>
                                    <input type="text" wire:model="payment_pakasir_base_url" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Slug</label>
                                    <input type="text" wire:model="payment_pakasir_slug" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">API Key</label>
                                    <input type="password" wire:model="payment_pakasir_api_key" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                            </div>
                        </div>
                    @elseif($payment_gateway === 'midtrans')
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                            <p class="text-xs font-bold text-slate-800">Konfigurasi Midtrans</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Server Key</label>
                                    <input type="password" wire:model="payment_midtrans_server_key" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Client Key</label>
                                    <input type="password" wire:model="payment_midtrans_client_key" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Mode</label>
                                    <select wire:model="payment_midtrans_is_production" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
                                        <option value="false">Sandbox</option>
                                        <option value="true">Production</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @elseif($payment_gateway === 'xendit')
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                            <p class="text-xs font-bold text-slate-800">Konfigurasi Xendit</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Secret Key</label>
                                    <input type="password" wire:model="payment_xendit_secret_key" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Callback Token</label>
                                    <input type="password" wire:model="payment_xendit_callback_token" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                            </div>
                        </div>
                    @elseif($payment_gateway === 'custom')
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                            <p class="text-xs font-bold text-slate-800">Konfigurasi Custom Webhook Gateway</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Checkout URL Template</label>
                                    <input type="text" wire:model="payment_custom_checkout_url" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Status URL Template</label>
                                    <input type="text" wire:model="payment_custom_status_url" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">API Key</label>
                                    <input type="password" wire:model="payment_custom_api_key" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Webhook Secret</label>
                                    <input type="password" wire:model="payment_custom_webhook_secret" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs" />
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Bank Account for Manual Transfer --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-base font-bold text-slate-800">Rekening Transfer Bank (Manual)</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Ditampilkan ke customer saat memilih Transfer Bank Manual di `/shop/checkout` dan detail pesanan.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Bank</label>
                            <input type="text" wire:model.live="payment_bank_name" placeholder="BCA / BRI / Mandiri / BSI"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Rekening</label>
                            <input type="text" wire:model.live="payment_bank_account_number" placeholder="1234567890"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm font-mono text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Atas Nama</label>
                            <input type="text" wire:model.live="payment_bank_account_holder" placeholder="PT Skillage Edupark"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800 focus:border-sky-500 focus:bg-white focus:ring-2 focus:ring-sky-500/20" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-sky-700 transition-colors">
                            <x-heroicon-o-check class="h-4 w-4" />
                            <span>Simpan Pembayaran &amp; Rekening</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Live Checkout Preview --}}
            <div class="lg:col-span-4 space-y-4">
                <div class="sticky top-20 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Preview di Checkout</p>

                    <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 text-xs text-slate-800 space-y-1">
                        <p class="font-bold text-slate-900">Rekening Tujuan Transfer</p>
                        @if(trim($payment_bank_account_number) !== '')
                            <p>Bank: <strong>{{ $payment_bank_name ?: '-' }}</strong></p>
                            <p>No. Rekening: <strong class="font-mono text-sm">{{ $payment_bank_account_number }}</strong></p>
                            <p>Atas Nama: <strong>{{ $payment_bank_account_holder ?: $brand_name }}</strong></p>
                        @else
                            <p class="text-slate-500">Isi nomor rekening di sebelah kiri untuk menampilkan info transfer ke customer.</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs space-y-1">
                        <p class="font-semibold text-slate-700">Gateway Online Terpilih</p>
                        <p class="font-mono text-sky-700 font-bold">{{ strtoupper($payment_gateway) }}</p>
                    </div>
                </div>
            </div>
        </form>
    @endif

    {{-- TAB 3: MATA UANG & SALDO AWAL --}}
    @if($activeTab === 'currency')
        <form wire:submit="saveCurrency" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="text-base font-bold text-slate-800">Format Mata Uang &amp; Saldo Awal</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Mengatur tampilan nominal di seluruh laporan, POS, dan katalog.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Simbol Mata Uang</label>
                        <input type="text" wire:model.live="currency_symbol"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Posisi Simbol</label>
                        <select wire:model.live="currency_position"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800">
                            <option value="left">Kiri (Rp 1.250.000)</option>
                            <option value="right">Kanan (1.250.000 Rp)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Digit Desimal</label>
                        <select wire:model.live="currency_fraction_digits"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800">
                            <option value="0">0 (Tanpa desimal)</option>
                            <option value="1">1 digit</option>
                            <option value="2">2 digit</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pemisah Ribuan</label>
                            <input type="text" wire:model.live="currency_thousand_separator" maxlength="3"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm font-mono text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pemisah Desimal</label>
                            <input type="text" wire:model.live="currency_decimal_separator" maxlength="3"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm font-mono text-slate-800" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Saldo Awal</label>
                        <input type="date" wire:model="opening_balance_date"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-800" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nominal Saldo Awal</label>
                        <input type="number" step="0.01" wire:model="opening_balance_amount"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm font-mono text-slate-800" />
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-sky-700 transition-colors">
                        <x-heroicon-o-check class="h-4 w-4" />
                        <span>Simpan Mata Uang &amp; Saldo</span>
                    </button>
                </div>
            </div>

            <div class="lg:col-span-4">
                <div class="sticky top-20 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Preview Format Nominal</p>
                    <div class="rounded-xl border border-sky-200 bg-sky-50/60 p-4">
                        <p class="text-[11px] text-slate-500">Contoh tampilan harga:</p>
                        <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $this->previewCurrency }}</p>
                    </div>
                </div>
            </div>
        </form>
    @endif

    {{-- TAB 4: SEMUA KEY (LANJUTAN) --}}
    @if($activeTab === 'advanced')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <livewire:settings.setting-table />
            <livewire:settings.setting-form />
        </div>
    @endif
</div>
