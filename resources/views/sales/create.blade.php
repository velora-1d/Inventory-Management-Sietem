<x-app-layout title="POS">
    <div class="mx-auto sm:px-6 lg:px-8 py-3"
         x-data="pos()"
         x-init="init()"
         @keydown.window.f1.prevent="$refs.searchInput.focus()"
         @keydown.window.f2.prevent="customerTs && customerTs.focus()"
         @keydown.window.f3.prevent="openConfirmation()"
         @keydown.window.f4.prevent="openCustomerModal()"
    >
        <div class="flex flex-col lg:flex-row h-[calc(100vh-110px)] space-y-4 lg:space-y-0 lg:space-x-4 relative">

            <!-- LEFT COLUMN: CART & PAYMENT PANEL (42% Width) -->
            <div class="w-full lg:w-[42%] flex flex-col h-full bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Header -->
                <div class="p-3.5 border-b border-gray-150 bg-gray-50 flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Cart & Payment details</span>
                    <span class="text-xs font-semibold text-sky-600 bg-sky-50 px-2 py-0.5 rounded-full" x-text="cart.reduce((sum, item) => sum + parseInt(item.quantity), 0) + ' items'"></span>
                </div>

                <div class="flex-1 flex flex-col overflow-y-auto p-4 space-y-4 min-h-0">
                    <!-- Customer Section -->
                    <div class="bg-sky-50 rounded-lg p-3 border border-sky-100 relative group">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-bold text-sky-500 uppercase">Customer</span>
                            <button @click="openCustomerModal()" class="text-[10px] font-semibold text-sky-600 hover:text-white hover:bg-sky-600 border border-sky-200 bg-white px-2 py-1 rounded transition-colors flex items-center">
                                + New (F4)
                            </button>
                        </div>

                        <div class="relative">
                            <template x-if="selectedCustomer">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="font-bold text-sm text-gray-900" x-text="selectedCustomer.name"></h3>
                                        <p class="text-xs text-gray-600" x-text="selectedCustomer.phone || 'No Phone'"></p>
                                    </div>
                                    <button @click="resetCustomer()" class="text-gray-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </template>
                            <div x-show="!selectedCustomer">
                                <select
                                    x-ref="customerSelect"
                                    placeholder="Search Customer [F2]..."
                                    autocomplete="off"
                                ></select>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Table Container -->
                    <div class="flex-1 min-h-[180px] bg-slate-50 rounded-lg border border-gray-200 overflow-hidden flex flex-col">
                        <div class="overflow-y-auto flex-1">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-100 sticky top-0 z-10">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                                        <th scope="col" class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                        <th scope="col" class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Price</th>
                                        <th scope="col" class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-150">
                                    <template x-for="(item, index) in cart" :key="item.id">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <!-- Product details -->
                                            <td class="px-3 py-2.5">
                                                <div class="font-medium text-gray-900 line-clamp-1" x-text="item.name"></div>
                                                <div class="text-[10px] text-gray-400 font-mono" x-text="item.sku"></div>
                                                <!-- Unit Price + Discount info inside name cell if discount exists -->
                                                <div class="text-[10px] text-gray-500 mt-0.5 flex items-center gap-1.5" x-show="item.discount > 0">
                                                    <span class="line-through" x-text="formatCurrency(item.price)"></span>
                                                    <span class="text-red-500 font-medium" x-text="'-' + formatCurrency(item.discount)"></span>
                                                </div>
                                            </td>
                                            <!-- Qty -->
                                            <td class="px-3 py-2.5 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <input type="number" x-model="item.quantity" min="1" :max="item.max_stock"
                                                        @input="validateQty(index)"
                                                        class="w-14 text-center border-gray-300 rounded-md focus:ring-sky-500 focus:border-sky-500 text-xs shadow-sm p-1">
                                                    <span class="text-[9px] text-gray-400 mt-0.5" x-text="item.unit"></span>
                                                </div>
                                            </td>
                                            <!-- Price -->
                                            <td class="px-3 py-2.5 text-right font-semibold text-gray-800">
                                                <div class="text-xs" x-text="formatCurrency((item.price - item.discount) * item.quantity)"></div>
                                                <!-- Action to edit discount per item -->
                                                <button @click="editItemDiscount(index)" class="text-[10px] text-sky-600 hover:underline block mt-0.5 ml-auto font-normal">
                                                    + Disc
                                                </button>
                                            </td>
                                            <!-- Delete Button -->
                                            <td class="px-3 py-2.5 text-center">
                                                <button @click="removeFromCart(index)" class="flex items-center justify-center w-7 h-7 rounded-full bg-red-50 text-red-500 hover:bg-red-100 transition-colors mx-auto">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="cart.length === 0">
                                        <tr>
                                            <td colspan="4" class="px-3 py-16 text-center text-gray-400">
                                                <div class="flex flex-col items-center justify-center">
                                                    <svg class="w-10 h-10 text-gray-300 mb-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                    <p class="text-xs font-semibold">Cart is empty</p>
                                                    <p class="text-[11px] text-gray-400 mt-0.5">Click products on the right catalog grid to add</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Summary (Fixed height) -->
                <div class="p-4 border-t border-gray-200 bg-gray-55 space-y-4">
                    <!-- Totals Section -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-gray-600 text-xs font-semibold">
                            <span>Subtotal</span>
                            <span x-text="formatCurrency(subtotal)"></span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600 text-xs font-semibold" x-show="totalDiscount > 0">
                            <span>Product Discounts</span>
                            <span class="text-red-500" x-text="'-' + formatCurrency(totalDiscount)"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs font-semibold">
                             <span class="text-gray-500">Discount (Global)</span>
                             <div class="relative w-28">
                                <div class="absolute inset-y-0 flex items-center pointer-events-none" :class="window.currencyPosition === 'left' ? 'left-0 pl-2' : 'right-0 pr-2'">
                                    <span class="text-gray-400 text-[10px]" x-text="window.currencySymbol"></span>
                                </div>
                                <input
                                    type="text"
                                    :value="formatNumber(globalDiscount)"
                                    @input="globalDiscount = unformatNumber($event.target.value)"
                                    class="focus:ring-sky-500 focus:border-sky-500 block w-full text-right text-xs border-gray-300 rounded-md py-1"
                                    :class="window.currencyPosition === 'left' ? 'pl-6 pr-2' : 'pr-6 pl-2'"
                                    placeholder="0"
                                >
                             </div>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-150">
                            <span class="text-sm font-bold text-gray-800">TOTAL</span>
                            <span class="text-xl font-extrabold text-sky-600" x-text="formatCurrency(total)"></span>
                        </div>
                    </div>

                    <!-- Payment Inputs -->
                    <div class="space-y-3 pt-3 border-t border-gray-150">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider shrink-0 w-24">Pay Method</span>
                            <div class="grid grid-cols-2 gap-2 flex-1">
                                <button
                                    @click="payment.method = 'cash'"
                                    class="px-2 py-1.5 text-xs font-bold rounded-lg border transition-all"
                                    :class="payment.method === 'cash' ? 'bg-sky-600 text-white border-sky-600 shadow-sm' : 'bg-white text-gray-700 border-gray-350 hover:bg-gray-50'"
                                >
                                    CASH
                                </button>
                                <button
                                    @click="payment.method = 'transfer'"
                                    class="px-2 py-1.5 text-xs font-bold rounded-lg border transition-all"
                                    :class="payment.method === 'transfer' ? 'bg-sky-600 text-white border-sky-600 shadow-sm' : 'bg-white text-gray-700 border-gray-350 hover:bg-gray-50'"
                                >
                                    TRANSFER
                                </button>
                            </div>
                        </div>

                        <template x-if="payment.method === 'cash'">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Received</span>
                                    <div class="relative flex-1">
                                        <div class="absolute inset-y-0 flex items-center pointer-events-none" :class="window.currencyPosition === 'left' ? 'left-0 pl-2.5' : 'right-0 pr-2.5'">
                                            <span class="text-gray-400 text-xs font-bold" x-text="window.currencySymbol"></span>
                                        </div>
                                        <input
                                            type="text"
                                            :value="formatNumber(payment.cash_received)"
                                            @input="payment.cash_received = unformatNumber($event.target.value)"
                                            class="block w-full py-1 text-right text-sm font-bold text-gray-900 border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500"
                                            :class="window.currencyPosition === 'left' ? 'pl-8 pr-2.5' : 'pr-8 pl-2.5'"
                                            placeholder="0"
                                        >
                                    </div>
                                </div>
                                <!-- Quick Cash Options -->
                                <div class="grid grid-cols-4 gap-1 ml-24">
                                    <button @click="payment.cash_received = total" class="px-1 py-1 bg-gray-150 hover:bg-gray-200 border border-gray-300 rounded text-[10px] font-semibold text-gray-700">EXACT</button>
                                    <button @click="payment.cash_received = total + 100000" class="px-1 py-1 bg-gray-150 hover:bg-gray-200 border border-gray-300 rounded text-[10px] font-semibold text-gray-700">+100K</button>
                                    <button @click="payment.cash_received = total + 50000" class="px-1 py-1 bg-gray-150 hover:bg-gray-200 border border-gray-300 rounded text-[10px] font-semibold text-gray-700">+50K</button>
                                    <button @click="payment.cash_received = total + 20000" class="px-1 py-1 bg-gray-150 hover:bg-gray-200 border border-gray-300 rounded text-[10px] font-semibold text-gray-700">+20K</button>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider w-24">Notes</span>
                            <textarea
                                x-model="payment.notes"
                                rows="1"
                                class="block flex-1 text-xs border-gray-350 rounded-lg focus:ring-sky-500 focus:border-sky-500 placeholder-gray-400 py-1 px-2 resize-none"
                                placeholder="Notes/Remarks..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button
                            type="button"
                            @click="$dispatch('open-modal', { name: 'cancel-modal' })"
                            class="py-2.5 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold transition-all text-center"
                        >
                            CANCEL
                        </button>
                        <button
                            @click="openConfirmation()"
                            class="py-2.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-extrabold shadow-sm transition-all text-center"
                        >
                            PAY (F3)
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: PRODUCT CATALOG & GRID (58% Width) -->
            <div class="w-full lg:w-[58%] flex flex-col h-full overflow-hidden">
                <!-- Search & Filters Container (Fixed) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 space-y-4">
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </div>
                        <input
                            x-ref="searchInput"
                            x-model="searchQuery"
                            type="text"
                            placeholder="Search Products (Name or SKU) [F1]..."
                            class="pl-10 pr-4 py-2.5 block w-full rounded-lg border-gray-300 focus:ring-sky-500 focus:border-sky-500 text-sm shadow-sm"
                        >
                        <!-- Clear Search Button -->
                        <button x-show="searchQuery" @click="searchQuery = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Category Slider (Horizontal Scroll) -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide shrink-0">
                        <button
                            @click="selectedCategoryId = null"
                            class="px-3.5 py-1.5 rounded-full text-xs font-bold tracking-wide transition-all border whitespace-nowrap"
                            :class="selectedCategoryId === null ? 'bg-sky-600 border-sky-650 text-white shadow-sm' : 'bg-gray-50 border-gray-200 text-slate-600 hover:bg-slate-100'"
                        >
                            All Products
                        </button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button
                                @click="selectedCategoryId = cat.id"
                                class="px-3.5 py-1.5 rounded-full text-xs font-bold tracking-wide transition-all border whitespace-nowrap"
                                :class="selectedCategoryId === cat.id ? 'bg-sky-600 border-sky-650 text-white shadow-sm' : 'bg-gray-50 border-gray-200 text-slate-600 hover:bg-slate-100'"
                                x-text="cat.name"
                            ></button>
                        </template>
                    </div>
                </div>

                <!-- Products Grid Scrollable area -->
                <div class="flex-1 overflow-y-auto min-h-0 bg-slate-50/50 rounded-xl p-0.5 border border-transparent">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div
                                @click="product.quantity > 0 ? addToCart(product) : null"
                                class="bg-white rounded-xl border border-gray-200 p-2.5 shadow-sm transition-all select-none relative flex flex-col h-full group"
                                :class="product.quantity > 0 ? 'hover:shadow-md cursor-pointer hover:border-sky-300' : 'opacity-60 cursor-not-allowed'"
                            >
                                <!-- Stock Badge (Top-left absolute) -->
                                <div class="absolute top-2 left-2 z-10">
                                    <span
                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded-full shadow-sm"
                                        :class="product.quantity > product.min_stock ? 'bg-sky-50 text-sky-700' : 'bg-red-50 text-red-700'"
                                        x-text="'Stock: ' + product.quantity"
                                    ></span>
                                </div>

                                <!-- Image Container (Fallback SVG if null) -->
                                <div class="aspect-square w-full rounded-lg border border-gray-150 overflow-hidden bg-slate-50 flex items-center justify-center shrink-0 mb-2 relative">
                                    <template x-if="product.image">
                                        <img :src="'/storage/' + product.image" class="w-full h-full object-cover transition-transform group-hover:scale-105">
                                    </template>
                                    <template x-if="!product.image">
                                        <div class="text-sky-500">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </div>
                                    </template>

                                    <!-- Sold Out Overlay -->
                                    <template x-if="product.quantity <= 0">
                                        <div class="absolute inset-0 bg-slate-900 bg-opacity-40 flex items-center justify-center">
                                            <span class="bg-red-600 text-white text-[10px] font-extrabold px-2 py-1 rounded shadow uppercase tracking-wider">Sold Out</span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 flex flex-col">
                                    <div class="text-[9px] font-mono text-slate-400 mb-0.5" x-text="product.sku"></div>
                                    <h4 class="text-xs font-bold text-slate-800 line-clamp-2 leading-snug mb-2 flex-1" x-text="product.name"></h4>
                                    
                                    <div class="flex items-center justify-between mt-auto">
                                        <span class="text-xs font-extrabold text-slate-900" x-text="formatCurrency(product.selling_price)"></span>
                                        <span class="text-[9px] font-medium text-slate-400 uppercase bg-slate-100 px-1 rounded" x-text="product.unit?.symbol || ''"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty search state -->
                        <template x-if="filteredProducts.length === 0">
                            <div class="col-span-full py-16 text-center text-slate-400">
                                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-sm font-semibold">No products found</p>
                                <p class="text-xs text-slate-400 mt-1">Try typing another keyword or check category filters</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <!-- Alpine Component Logic -->
        <script>
            function pos() {
                return {
                    // Products & Categories loaded from controller
                    products: @json($products),
                    categories: @json($categories),
                    searchQuery: '',
                    selectedCategoryId: null,

                    cart: [],
                    selectedCustomer: null,
                    payment: {
                        method: 'cash',
                        cash_received: 0,
                        notes: ''
                    },
                    globalDiscount: 0,
                    saleStatus: 'completed',
                    isSubmitting: false,

                    // TomSelect Instance
                    customerTs: null,

                    init() {
                         // Load from LocalStorage
                        const savedCart = localStorage.getItem('pos_cart');
                        if (savedCart) this.cart = JSON.parse(savedCart);

                        const savedCustomer = localStorage.getItem('pos_customer');
                        if (savedCustomer) this.selectedCustomer = JSON.parse(savedCustomer);

                        const savedPayment = localStorage.getItem('pos_payment');
                        if (savedPayment) this.payment = JSON.parse(savedPayment);

                        const savedGlobalDiscount = localStorage.getItem('pos_globalDiscount');
                        if (savedGlobalDiscount) this.globalDiscount = parseInt(savedGlobalDiscount);

                        // Watchers
                        this.$watch('cart', (val) => localStorage.setItem('pos_cart', JSON.stringify(val)));
                        this.$watch('selectedCustomer', (val) => localStorage.setItem('pos_customer', JSON.stringify(val)));
                        this.$watch('payment', (val) => localStorage.setItem('pos_payment', JSON.stringify(val)));
                        this.$watch('globalDiscount', (val) => localStorage.setItem('pos_globalDiscount', val));

                        this.initCustomerSelect();
                    },

                    // Computed product filter
                    get filteredProducts() {
                        let query = this.searchQuery.toLowerCase().trim();
                        return this.products.filter(product => {
                            let matchesQuery = !query || 
                                product.name.toLowerCase().includes(query) || 
                                (product.sku && product.sku.toLowerCase().includes(query));
                            
                            let matchesCategory = this.selectedCategoryId === null || 
                                product.category_id == this.selectedCategoryId;
                            
                            return matchesQuery && matchesCategory;
                        });
                    },

                    initCustomerSelect() {
                        if (!this.$refs.customerSelect) return;

                        if (this.customerTs) {
                            this.customerTs.destroy();
                            this.customerTs = null;
                        }

                        this.customerTs = new TomSelect(this.$refs.customerSelect, {
                            valueField: 'value',
                            labelField: 'text',
                            searchField: 'text',
                            preload: 'focus', 
                            openOnFocus: true, 
                            load: (query, callback) => {
                                var url = '{{ route("ajax.customers.search") }}';

                                fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({ q: query })
                                })
                                    .then(response => response.json())
                                    .then(json => {
                                        callback(json);
                                    }).catch(()=>{
                                        callback();
                                    });
                            },
                            render: {
                                option: (item, escape) => {
                                    return `
                                        <div class="py-2 px-3 hover:bg-sky-50">
                                            <div class="font-medium text-gray-900">${escape(item.name)}</div>
                                            <div class="text-xs text-gray-500">${escape(item.phone || 'No Phone')}</div>
                                        </div>
                                    `;
                                }
                            },
                            onChange: (value) => {
                                if (value) {
                                    const item = this.customerTs.options[value];
                                    if(item) {
                                        this.selectedCustomer = {
                                            id: item.value,
                                            name: item.name,
                                            phone: item.phone,
                                            email: item.email || '',
                                            address: item.address || ''
                                        };
                                        this.customerTs.clear();
                                    }
                                }
                            }
                        });
                    },

                    resetCustomer() {
                        this.selectedCustomer = null;
                        this.$nextTick(() => {
                            this.customerTs && this.customerTs.focus();
                        });
                    },

                    clearStorage() {
                        localStorage.removeItem('pos_cart');
                        localStorage.removeItem('pos_customer');
                        localStorage.removeItem('pos_payment');
                        localStorage.removeItem('pos_globalDiscount');
                    },

                    // Cart Management
                    addToCart(product) {
                        const existing = this.cart.find(item => item.id === product.id);
                        if (existing) {
                            if (existing.quantity < product.quantity) {
                                existing.quantity++;
                                this.$dispatch('toast', { message: 'Jumlah diperbarui di keranjang.', type: 'info' });
                            } else {
                                this.$dispatch('toast', { message: 'Stok tidak mencukupi!', type: 'error' });
                            }
                        } else {
                            if (product.quantity > 0) {
                                this.cart.push({
                                    id: product.id,
                                    name: product.name,
                                    sku: product.sku,
                                    price: product.selling_price,
                                    quantity: 1,
                                    max_stock: product.quantity,
                                    unit: product.unit ? product.unit.symbol : '',
                                    discount: 0
                                });
                                this.$dispatch('toast', { message: 'Produk berhasil ditambahkan ke keranjang.', type: 'success' });
                            } else {
                                this.$dispatch('toast', { message: 'Stok habis!', type: 'error' });
                            }
                        }
                    },

                    validateQty(index) {
                        const item = this.cart[index];
                        if (item.quantity > item.max_stock) {
                            item.quantity = item.max_stock;
                            this.$dispatch('toast', { message: 'Batas maksimum stok telah tercapai.', type: 'warning' });
                        }
                        if (item.quantity < 1) item.quantity = 1;
                    },

                    removeFromCart(index) {
                        const removedItem = this.cart[index];
                        this.cart.splice(index, 1);
                        this.$dispatch('toast', { message: 'Produk dihapus dari keranjang.', type: 'info' });
                    },

                    editItemDiscount(index) {
                        const item = this.cart[index];
                        const input = prompt("Enter Discount per Unit (Rp) for " + item.name + ":", item.discount);
                        if (input !== null) {
                            const discVal = parseInt(input.replace(/[^0-9]/g, '')) || 0;
                            if (discVal >= 0 && discVal <= item.price) {
                                item.discount = discVal;
                                this.$dispatch('toast', { message: 'Diskon berhasil diperbarui.', type: 'success' });
                            } else {
                                this.$dispatch('toast', { message: 'Nilai diskon tidak valid!', type: 'error' });
                            }
                        }
                    },

                    // Customer Modal Open
                    openCustomerModal() {
                        this.$dispatch('open-modal', { name: 'customer-modal' });
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.$refs.nameInput && this.$refs.nameInput.focus();
                            }, 100); 
                        });
                    },

                    // Computed properties
                    get subtotal() {
                        return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    },

                    get totalDiscount() {
                        return this.cart.reduce((sum, item) => sum + (item.discount * item.quantity), 0);
                    },

                    get total() {
                        return Math.max(0, this.subtotal - this.totalDiscount - this.globalDiscount);
                    },

                    get change() {
                        if (this.payment.method !== 'cash') return 0;
                        return Math.max(0, this.payment.cash_received - this.total);
                    },

                    // Helpers
                    formatCurrency(value) {
                        return window.formatMoney(value);
                    },

                    unformatNumber(value) {
                        if(typeof value !== 'string') return value || 0;
                        let raw = value;
                        if(window.thousandSeparator) {
                            raw = raw.split(window.thousandSeparator).join('');
                        }
                        if(window.decimalSeparator && window.decimalSeparator !== '.') {
                            raw = raw.replace(window.decimalSeparator, '.');
                        }
                        raw = raw.replace(/[^0-9\.-]/g, '');
                        if (raw === '' || raw === '-') return 0;
                        if (raw.endsWith('.')) return raw;
                        return parseFloat(raw) || 0;
                    },

                    formatNumber(value) {
                        if (typeof value === 'string' && value.endsWith('.')) {
                             return value.replace('.', window.decimalSeparator);
                        }
                        let amount = parseFloat(value) || 0;
                        let isNegative = amount < 0;
                        amount = Math.abs(amount);
                        let strAmount = amount.toString();
                        let parts = strAmount.split('.');
                        let integerPart = parts[0];
                        let decimalPart = parts.length > 1 ? window.decimalSeparator + parts[1] : '';

                        let rgx = /(\d+)(\d{3})/;
                        while (rgx.test(integerPart)) {
                            integerPart = integerPart.replace(rgx, '$1' + window.thousandSeparator + '$2');
                        }
                        let num = integerPart + decimalPart;
                        return isNegative ? '-' + num : num;
                    },

                    // Confirmation
                    openConfirmation() {
                        if (this.cart.length === 0) return;
                        if (this.payment.method === 'cash' && this.payment.cash_received < this.total) {
                            this.$dispatch('toast', { message: 'Jumlah pembayaran kurang dari total tagihan!', type: 'error' });
                            return;
                        }
                        this.$dispatch('open-modal', { name: 'confirmation-modal' });
                    },

                    // Submit Sale
                    async submitSale() {
                        this.isSubmitting = true;

                        try {
                            const items = this.cart.map(item => ({
                                product_id: item.id,
                                quantity: item.quantity,
                                unit_price: item.price,
                                discount: item.discount
                            }));

                            const payload = {
                                customer_id: this.selectedCustomer?.id,
                                items: items,
                                payment_method: this.payment.method,
                                cash_received: this.payment.cash_received,
                                notes: this.payment.notes,
                                global_discount: this.globalDiscount,
                                status: this.saleStatus,
                                sale_date: new Date().toISOString().slice(0, 10),
                                _token: '{{ csrf_token() }}'
                            };

                            const res = await fetch('{{ route("sales.store") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify(payload)
                            });

                            const data = await res.json();

                            if (res.ok && data.success) {
                                this.$dispatch('close-modal', { name: 'confirmation-modal' });

                                if (data.print_url) {
                                    window.open(data.print_url, '_blank');
                                }

                                this.clearStorage();
                                this.resetForm();

                                // Update local stocks in catalog list dynamically without reload!
                                items.forEach(item => {
                                    const prod = this.products.find(p => p.id === item.product_id);
                                    if (prod) {
                                        prod.quantity = Math.max(0, prod.quantity - item.quantity);
                                    }
                                });

                                this.$dispatch('toast', { message: 'Transaksi berhasil diproses!', type: 'success' });

                            } else {
                                this.$dispatch('toast', { message: data.message || 'Terjadi kesalahan saat memproses transaksi.', type: 'error' });
                            }

                        } catch (e) {
                            console.error(e);
                            this.$dispatch('toast', { message: 'Terjadi kesalahan jaringan. Coba lagi.', type: 'error' });
                        } finally {
                            this.isSubmitting = false;
                        }
                    },

                    resetForm() {
                        this.cart = [];
                        this.selectedCustomer = null;
                        this.payment = {
                            method: 'cash',
                            cash_received: 0,
                            notes: ''
                        };
                        this.globalDiscount = 0;
                        this.customerTs && this.customerTs.clear();
                    }
                }
            }
        </script>

        <!-- POS Payment Confirmation Modal (Premium) -->
        <div
            x-data="{ modalShow: false }"
            x-on:open-modal.window="if ($event.detail.name === 'confirmation-modal') modalShow = true"
            x-on:close-modal.window="if ($event.detail.name === 'confirmation-modal') modalShow = false"
            x-on:keydown.escape.window="modalShow = false"
            class="relative z-[200]"
            style="display: block;"
        >
            <!-- Backdrop -->
            <div
                x-show="modalShow"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 backdrop-blur-sm bg-black/40 z-[199]"
                style="display: none;"
            ></div>

            <div x-show="modalShow" class="fixed inset-0 z-[200] overflow-y-auto" style="display: none;">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        x-show="modalShow"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                        @click.away="modalShow = false"
                    >
                        <!-- Gradient header -->
                        <div class="bg-gradient-to-r from-sky-600 to-indigo-600 px-6 py-5">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white/20 backdrop-blur-sm">
                                    <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">Konfirmasi Pembayaran</h3>
                                    <p class="text-xs text-sky-100 mt-0.5">Periksa detail sebelum memproses transaksi</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <!-- Summary Grid -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between text-sm py-1.5">
                                    <span class="text-gray-500 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        Total Item
                                    </span>
                                    <span class="font-semibold text-gray-900" x-text="cart.reduce((sum, item) => sum + parseInt(item.quantity), 0) + ' pcs'"></span>
                                </div>

                                <div class="flex items-center justify-between text-sm py-1.5">
                                    <span class="text-gray-500 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" />
                                        </svg>
                                        Subtotal
                                    </span>
                                    <span class="font-semibold text-gray-900" x-text="formatCurrency(subtotal)"></span>
                                </div>

                                <div class="flex items-center justify-between text-sm py-1.5 text-red-500" x-show="totalDiscount > 0">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" />
                                        </svg>
                                        Diskon Produk
                                    </span>
                                    <span class="font-semibold" x-text="'- ' + formatCurrency(totalDiscount)"></span>
                                </div>

                                <div class="flex items-center justify-between text-sm py-1.5 text-red-500" x-show="globalDiscount > 0">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" />
                                        </svg>
                                        Extra Discount
                                    </span>
                                    <span class="font-semibold" x-text="'- ' + formatCurrency(globalDiscount)"></span>
                                </div>

                                <!-- Total -->
                                <div class="flex items-center justify-between bg-sky-50 border border-sky-100 rounded-xl px-4 py-3 mt-1">
                                    <span class="text-sm font-bold text-sky-800 flex items-center gap-1.5">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                        </svg>
                                        Total Tagihan
                                    </span>
                                    <span class="text-lg font-black text-sky-700" x-text="formatCurrency(total)"></span>
                                </div>

                                <!-- Cash received & change (cash method only) -->
                                <template x-if="payment.method === 'cash'">
                                    <div class="space-y-2 pt-1">
                                        <div class="flex items-center justify-between text-sm py-1">
                                            <span class="text-gray-500 flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
                                                </svg>
                                                Uang Diterima
                                            </span>
                                            <span class="font-semibold text-gray-900" x-text="formatCurrency(payment.cash_received)"></span>
                                        </div>
                                        <div class="flex items-center justify-between bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5">
                                            <span class="text-sm font-bold text-emerald-700 flex items-center gap-1.5">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Kembalian
                                            </span>
                                            <span class="text-base font-black text-emerald-700" x-text="formatCurrency(change)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Sale Status Selection -->
                            <div class="mt-5">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Status Penjualan</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        @click="saleStatus = 'completed'"
                                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border flex items-center justify-center gap-1.5 transition-all duration-150"
                                        :class="saleStatus === 'completed' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm shadow-emerald-100' : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-300 hover:text-emerald-600'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Lunas
                                    </button>
                                    <button
                                        @click="saleStatus = 'pending'"
                                        class="px-4 py-2.5 text-sm font-semibold rounded-xl border flex items-center justify-center gap-1.5 transition-all duration-150"
                                        :class="saleStatus === 'pending' ? 'bg-amber-500 text-white border-amber-500 shadow-sm shadow-amber-100' : 'bg-white text-gray-600 border-gray-200 hover:border-amber-300 hover:text-amber-600'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Tertunda
                                    </button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-5 flex flex-col gap-2.5">
                                <button
                                    @click="submitSale()"
                                    :disabled="isSubmitting"
                                    class="w-full flex justify-center items-center py-3 px-4 rounded-xl shadow-sm text-sm font-bold text-white focus:outline-none disabled:opacity-50 transition-all active:scale-[0.98] bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 shadow-sky-200"
                                >
                                    <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg x-show="!isSubmitting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span x-text="isSubmitting ? 'Memproses...' : 'PROSES PENJUALAN'"></span>
                                </button>

                                <button
                                    type="button"
                                    @click="modalShow = false"
                                    class="w-full flex justify-center items-center py-2.5 px-4 rounded-xl text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-all"
                                >
                                    <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Customer Modal -->
        <x-modal name="customer-modal" focusable>
            <div class="p-6" x-data="{
                newCust: { name: '', email: '', phone: '', address: '', notes: '' },
                errors: {},
                loading: false,
                async save() {
                    this.errors = {}; 

                    if (!this.newCust.name.trim()) {
                        this.errors.name = 'Nama wajib diisi.';
                        return;
                    }

                    this.loading = true;
                    try {
                        const res = await fetch('{{ route("ajax.customers.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.newCust)
                        });
                        const data = await res.json();

                        if (res.ok) {
                            this.$dispatch('close-modal', { name: 'customer-modal' });
                            this.$dispatch('customer-created', data);
                            this.newCust = { name: '', email: '', phone: '', address: '', notes: '' };
                            this.errors = {};
                        } else {
                            if (data.errors) {
                                Object.keys(data.errors).forEach(key => {
                                    this.errors[key] = data.errors[key][0];
                                });
                            } else {
                                this.$dispatch('toast', { message: data.message || 'Gagal menambahkan pelanggan.', type: 'error' });
                            }
                        }
                    } catch(e) { console.error(e); }
                    finally { this.loading = false; }
                }
            }">
                <!-- Header -->
                <div class="mb-6 space-y-1.5 text-center sm:text-left border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-semibold leading-none tracking-tight text-foreground">
                        {{ __('Create New Customer') }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        {{ __('Add a new customer to your records for this sale.') }}
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <x-form-input
                            name="new_name"
                            label="Full Name"
                            x-model="newCust.name"
                            x-ref="nameInput"
                            required
                        />
                        <p x-show="errors.name" x-text="errors.name" class="text-sm font-medium text-red-600 mt-1" style="display: none;"></p>
                    </div>

                    <!-- Contact Info -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="w-full sm:w-1/2">
                            <x-form-input
                                name="new_email"
                                label="Email"
                                type="email"
                                x-model="newCust.email"
                            />
                            <p x-show="errors.email" x-text="errors.email" class="text-sm font-medium text-red-600 mt-1" style="display: none;"></p>
                        </div>
                        <div class="w-full sm:w-1/2">
                            <x-form-input
                                name="new_phone"
                                label="Phone"
                                x-model="newCust.phone"
                            />
                            <p x-show="errors.phone" x-text="errors.phone" class="text-sm font-medium text-red-600 mt-1" style="display: none;"></p>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="space-y-2">
                        <x-input-label for="new_address" :value="__('Address')" />
                        <textarea
                            id="new_address"
                            x-model="newCust.address"
                            rows="3"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                            placeholder="Full Address"
                        ></textarea>
                        <p x-show="errors.address" x-text="errors.address" class="text-sm font-medium text-red-600 mt-1" style="display: none;"></p>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <x-input-label for="new_notes" :value="__('Notes')" />
                        <textarea
                            id="new_notes"
                            x-model="newCust.notes"
                            rows="3"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                            placeholder="Additional notes..."
                        ></textarea>
                        <p x-show="errors.notes" x-text="errors.notes" class="text-sm font-medium text-red-600 mt-1" style="display: none;"></p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
                        <x-secondary-button type="button" x-on:click="$dispatch('close-modal', { name: 'customer-modal' })">
                            {{ __('Cancel') }}
                        </x-secondary-button>

                        <x-primary-button type="button" @click="save()" x-bind:disabled="loading">
                            <template x-if="loading">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="loading ? 'Saving...' : 'Save Customer'"></span>
                        </x-primary-button>
                    </div>
                </div>
            </div>
        </x-modal>

        <!-- Cancel Confirmation Modal -->
        <x-modal name="cancel-modal" focusable>
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                            Cancel Transaction?
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to cancel? All current items and selections will be lost.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                <x-danger-button @click="resetForm(); clearStorage(); $dispatch('close-modal', { name: 'cancel-modal' }); $dispatch('toast', { message: 'Transaksi dibatalkan.', type: 'info' })" class="w-full sm:w-auto justify-center">
                    {{ __('Yes, Cancel Transaction') }}
                </x-danger-button>
                <button
                    type="button"
                    @click="$dispatch('close-modal', { name: 'cancel-modal' })"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors"
                >
                    {{ __('No, Return') }}
                </button>
            </div>
        </x-modal>

        <!-- Listen for customer created -->
        <div @customer-created.window="selectedCustomer = $event.detail; customerSearch = '';"></div>
    </div>
</x-app-layout>
