<x-modal name="setting-form-modal" :title="''" maxWidth="2xl">
    <div class="p-6">
        <!-- Custom Header -->
        <div class="mb-6 space-y-1.5 text-center sm:text-left border-b border-gray-200 pb-4">
            <h3 class="text-lg font-semibold leading-none tracking-tight text-foreground">
                {{ __('Edit Setting') }}
            </h3>
            <p class="text-sm text-muted-foreground">
                {{ __('Update the value of this setting.') }}
            </p>
        </div>

        <form wire:submit="save" class="space-y-4">
            <!-- Label (Readonly) -->
            <div class="space-y-2">
                <x-input-label for="key" :value="__('Setting Name')" />
                <div class="px-3 py-2 text-sm font-medium border rounded-md border-input bg-muted/50 text-foreground">
                    {{ $label }}
                </div>
            </div>

            <!-- Value -->
            <div class="space-y-2">
                <x-input-label for="value" :value="__('Value')" />
                
                @if($key === 'currency_position')
                    <select id="value" wire:model="value" class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm">
                        <option value="left">Left (Example: Rp 10.000)</option>
                        <option value="right">Right (Example: 10.000 Rp)</option>
                    </select>
                @elseif($key === 'payment_gateway')
                    <select id="value" wire:model="value" class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm">
                        <option value="manual">Manual / Offline (Cash, Transfer, QRIS Statis)</option>
                        <option value="pakasir">Pakasir Payment Gateway</option>
                        <option value="verapay">VeraPay Payment Hub</option>
                        <option value="midtrans">Midtrans Snap</option>
                        <option value="xendit">Xendit Invoice</option>
                        <option value="custom">Custom / Generic Webhook Gateway</option>
                    </select>
                @elseif($key === 'payment_midtrans_is_production')
                    <select id="value" wire:model="value" class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm">
                        <option value="false">Sandbox (false)</option>
                        <option value="true">Production (true)</option>
                    </select>
                @elseif($key === 'payment_gateway_methods')
                    <input
                        type="text"
                        id="value"
                        wire:model="value"
                        class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        placeholder="qris,ewallet,virtual_account"
                    >
                    <p class="text-xs text-muted-foreground">Comma-separated methods routed via active online gateway (e.g. qris,ewallet,virtual_account,transfer).</p>
                @elseif(\Illuminate\Support\Str::endsWith((string) $key, ['_key', '_secret', '_token']))
                    <input
                        type="password"
                        id="value"
                        wire:model="value"
                        autocomplete="new-password"
                        class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        placeholder="Enter secret or leave blank..."
                    >
                @elseif($key === 'brand_logo')
                    <div class="space-y-3">
                        <div class="flex items-center gap-4 p-3 rounded-lg border border-slate-200 bg-slate-50">
                            @if($logoUpload)
                                <img src="{{ $logoUpload->temporaryUrl() }}" alt="Preview Logo" class="h-12 w-12 rounded-lg object-contain bg-white border border-slate-200 p-1">
                            @elseif($value)
                                <img src="{{ brand_logo_url() }}" alt="Current Logo" class="h-12 w-12 rounded-lg object-contain bg-white border border-slate-200 p-1">
                            @else
                                <x-application-logo class="h-12 w-12" />
                            @endif
                            <div class="text-xs text-slate-600">
                                <p class="font-semibold text-slate-800">{{ $value ? 'Custom Logo Aktif' : 'Default SVG Blueprint Aktif' }}</p>
                                <p>Format: PNG, JPG, WebP, atau SVG (maks. 2 MB).</p>
                            </div>
                        </div>

                        <input
                            type="file"
                            id="logoUpload"
                            wire:model="logoUpload"
                            accept=".png,.jpg,.jpeg,.webp,.svg"
                            class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100"
                        >
                        <x-input-error :messages="$errors->get('logoUpload')" />

                        @if($value)
                            <div>
                                <button type="button" wire:click="resetLogo" class="text-xs font-semibold text-red-600 hover:underline">
                                    Reset ke Logo Default SVG
                                </button>
                            </div>
                        @endif
                    </div>
                @elseif(in_array($key, ['brand_name', 'brand_subtitle', 'brand_short_code', 'brand_whatsapp_admin']))
                    <input
                        type="text"
                        id="value"
                        wire:model="value"
                        class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        placeholder="Enter value..."
                    >
                    <p class="text-xs text-muted-foreground">Perubahan branding langsung diterapkan ke seluruh tampilan aplikasi dan faktur cetak.</p>
                @elseif($key === 'currency_fraction_digits')
                    <input 
                        type="number" 
                        id="value" 
                        wire:model="value" 
                        min="0"
                        max="4"
                        class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm" 
                        placeholder="0 for IDR, 2 for USD"
                    >
                @elseif(in_array($key, ['currency_thousand_separator', 'currency_decimal_separator']))
                    <select id="value" wire:model="value" class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm">
                        <option value=".">Dot (.)</option>
                        <option value=",">Comma (,)</option>
                        <option value=" ">Space ( )</option>
                        <option value="">None</option>
                    </select>
                @elseif(in_array($key, ['store_address']))
                    <textarea
                        id="value"
                        wire:model="value"
                        rows="4"
                        class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        placeholder="Enter value..."
                    ></textarea>
                @else
                    <input 
                        type="text" 
                        id="value" 
                        wire:model="value" 
                        class="block w-full rounded-md border-input bg-background shadow-sm focus:border-ring focus:ring-ring sm:text-sm" 
                        placeholder="Enter value..."
                    >
                @endif
                <x-input-error :messages="$errors->get('value')" />
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', { name: 'setting-form-modal' })">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button type="submit" wire:loading.attr="disabled">
                    <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <x-heroicon-o-check wire:loading.remove wire:target="save" class="w-4 h-4 mr-2" />
                    {{ __('Save Changes') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
