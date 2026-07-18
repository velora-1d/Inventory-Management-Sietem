<x-modal name="product-detail-modal" focusable>
    @if($product)
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-border pb-4">
                <div>
                    <h3 class="text-xl font-bold text-foreground tracking-tight">{{ $product->name }}</h3>
                    <p class="text-sm text-muted-foreground font-mono">{{ $product->sku }}</p>
                </div>
                <div>
                    @if($product->is_active)
                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                            Inactive
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-6">
                <!-- Product Image -->
                <div class="w-full md:w-48 shrink-0">
                    <div class="aspect-square w-full rounded-xl border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center shadow-inner">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-16 h-16 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        @endif
                    </div>
                </div>

                <!-- Details Content -->
                <div class="flex-1 space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Category') }}</label>
                            <p class="text-sm text-slate-800 font-semibold">{{ $product->category->name ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Unit') }}</label>
                            <p class="text-sm text-slate-800 font-semibold">
                                @if($product->unit)
                                    {{ $product->unit->name }} <span class="text-slate-400 font-medium">({{ $product->unit->symbol }})</span>
                                @else
                                    -
                                  @endif
                            </p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Selling Price') }}</label>
                            <p class="text-sm text-slate-800 font-bold">@money($product->selling_price)</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Purchase Price') }}</label>
                            <p class="text-sm text-slate-800 font-bold">@money($product->purchase_price)</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Stock') }}</label>
                            <p class="text-sm font-semibold {{ $product->quantity <= $product->min_stock ? 'text-rose-500' : 'text-slate-800' }}">
                                {{ number_format($product->quantity) . ' ' . ($product->unit->symbol ?? '') }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Min Stock Alert') }}</label>
                            <p class="text-sm text-slate-800 font-semibold">{{ number_format($product->min_stock) }}</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Description') }}</label>
                        <p class="text-sm text-slate-700 leading-relaxed font-medium">
                            {{ $product->description ?: 'No description provided.' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Internal Notes') }}</label>
                        <div class="bg-gray-50 border border-gray-100 p-3 rounded-md">
                            <p class="text-sm text-slate-700 font-mono whitespace-pre-wrap leading-relaxed">{{ $product->notes ?: 'No notes.' }}</p>
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Created At') }}</label>
                            <p class="text-sm text-slate-600 font-medium">{{ $product->created_at?->format('d M Y, H:i') ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-muted-foreground">{{ __('Last Updated') }}</label>
                            <p class="text-sm text-slate-600 font-medium">{{ $product->updated_at?->format('d M Y, H:i') ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-x-2 pt-4 border-t border-border">
                <x-secondary-button type="button" x-on:click="$dispatch('close-modal', { name: 'product-detail-modal' })">
                    {{ __('Close') }}
                </x-secondary-button>
                <x-primary-button type="button" x-on:click="$dispatch('close-modal', { name: 'product-detail-modal' }); $dispatch('edit-product', { product: {{ $product->id }} })">
                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-2" />
                    {{ __('Edit Product') }}
                </x-primary-button>
            </div>
        </div>
    @else
        <div class="p-8 text-center flex flex-col items-center justify-center space-y-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            <span class="text-sm text-muted-foreground">{{ __('Loading details...') }}</span>
        </div>
    @endif
</x-modal>
