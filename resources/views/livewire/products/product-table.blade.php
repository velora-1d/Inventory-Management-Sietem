<div>
    <div class="bg-white rounded-xl border border-gray-150 p-4 mb-6 shadow-sm">
        <div class="grid grid-cols-4 gap-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    wire:model.live.debounce.300ms="search" 
                    type="text" 
                    placeholder="Search by name or SKU..." 
                    class="pl-9 w-full rounded-lg border-gray-200 text-sm focus:border-sky-500 focus:ring-sky-500/20 transition-all bg-gray-50/50"
                />
            </div>

            <div>
                <select 
                    wire:model.live="categoryId" 
                    class="w-full rounded-lg border-gray-200 text-sm focus:border-sky-500 focus:ring-sky-500/20 transition-all bg-gray-50/50"
                >
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select 
                    wire:model.live="unitId" 
                    class="w-full rounded-lg border-gray-200 text-sm focus:border-sky-500 focus:ring-sky-500/20 transition-all bg-gray-50/50"
                >
                    <option value="">All Units</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select 
                    wire:model.live="status" 
                    class="w-full rounded-lg border-gray-200 text-sm focus:border-sky-500 focus:ring-sky-500/20 transition-all bg-gray-50/50"
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    @if($products->isEmpty())
        <div class="bg-white rounded-xl border border-gray-150 py-16 px-4 text-center shadow-sm">
            <div class="w-16 h-16 bg-sky-50 text-sky-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">No Products Found</h3>
            <p class="text-sm text-gray-500 max-w-sm mx-auto">We couldn't find any products matching your search criteria or filters.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6 mb-6">
            @foreach($products as $product)
                <div class="bg-white rounded-2xl border border-gray-150 overflow-hidden shadow-sm hover:shadow-md hover:border-sky-200 transition-all duration-200 group flex flex-col">
                    <div class="relative bg-gray-50 h-44 shrink-0 flex items-center justify-center overflow-hidden border-b border-gray-100">
                        @if($product->image)
                            <img 
                                src="{{ Storage::url($product->image) }}" 
                                alt="{{ $product->name }}" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        @else
                            <div class="w-12 h-12 bg-sky-100 text-sky-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        @endif

                        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                            @if(!$product->is_active)
                                <span class="text-[10px] font-black tracking-wider uppercase px-2.5 py-1 rounded-lg bg-red-600 text-white shadow-sm">
                                    Discontinue
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-sky-600 uppercase tracking-widest block mb-1">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </span>
                            
                            <h4 class="font-bold text-gray-800 text-sm leading-snug mb-1 line-clamp-2" title="{{ $product->name }}">
                                {{ $product->name }}
                            </h4>

                            <span class="inline-block font-mono text-[10px] bg-slate-100 text-slate-500 rounded px-1.5 py-0.5 mb-3">
                                {{ $product->sku }}
                            </span>

                            <div class="flex items-center justify-between border-t border-b border-dashed border-gray-100 py-2 mb-3">
                                <div>
                                    <span class="text-[9px] text-gray-400 block uppercase font-semibold">Buy Price</span>
                                    <span class="text-xs font-bold text-gray-500">{{ format_money($product->purchase_price) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] text-gray-400 block uppercase font-semibold">Sell Price</span>
                                    <span class="text-sm font-black text-sky-600">{{ format_money($product->selling_price) }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs mb-4">
                                <span class="text-gray-500">Stock Available:</span>
                                @if($product->quantity <= $product->min_stock)
                                    <span class="flex items-center gap-1.5 font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100 animate-pulse">
                                        <span class="w-1.5 h-1.5 bg-red-600 rounded-full"></span>
                                        {{ $product->quantity }} {{ $product->unit?->symbol }} (Restock)
                                    </span>
                                @elseif($product->quantity <= $product->min_stock + 5)
                                    <span class="flex items-center gap-1.5 font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                        {{ $product->quantity }} {{ $product->unit?->symbol }} (Low)
                                    </span>
                                @else
                                    <span class="flex items-center gap-1.5 font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                        {{ $product->quantity }} {{ $product->unit?->symbol }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 pt-3 border-t border-gray-100 justify-end pg-theme-tailwind-td">
                                <button 
                                    wire:click="$dispatch('show-product', { product: {{ $product->id }} })" 
                                    class="bg-blue-500 text-white rounded-md p-2 flex items-center justify-center"
                                    title="View Detail"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                <button 
                                    wire:click="$dispatch('edit-product', { product: {{ $product->id }} })" 
                                    class="bg-amber-500 text-white rounded-md p-2 flex items-center justify-center"
                                    title="Edit Product"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <button 
                                    wire:click="$dispatch('open-delete-modal', {
                                        component: 'products.product-table',
                                        method: 'delete',
                                        params: { rowId: {{ $product->id }} },
                                        title: 'Delete Product?',
                                        description: 'Are you sure you want to delete product \'{{ addslashes($product->name) }}\'? This action cannot be undone.'
                                    })" 
                                    class="bg-red-500 text-white rounded-md p-2 flex items-center justify-center"
                                    title="Delete Product"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 bg-white border border-gray-150 rounded-xl p-4 shadow-sm">
            {{ $products->links() }}
        </div>
    @endif
</div>
