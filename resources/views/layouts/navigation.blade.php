<style>
    /* Premium Sidebar Styling */
    .sidebar-container {
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.1) transparent;
    }
    .sidebar-container::-webkit-scrollbar {
        width: 4px;
    }
    .sidebar-container::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.1);
        border-radius: 4px;
    }
    
    .sidebar-group-title {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: hsl(var(--muted-foreground));
        opacity: 0.7;
        padding: 16px 12px 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sidebar-group-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: hsl(var(--border));
        opacity: 0.35;
    }
    
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 9px 12px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        color: hsl(var(--muted-foreground));
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    
    .sidebar-link:hover {
        color: hsl(var(--foreground));
        background: rgba(0, 0, 0, 0.035);
        transform: translateX(4px);
    }
    
    .sidebar-link.active {
        color: #2563EB;
        background: rgba(37, 99, 235, 0.08);
        font-weight: 600;
    }
    
    .sidebar-link.active::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 25%;
        bottom: 25%;
        width: 3px;
        background: #2563EB;
        border-radius: 0 4px 4px 0;
    }

    /* SVG Nav Icons styling */
    .nav-icon {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        transition: transform 0.2s ease, color 0.2s ease;
    }

    .sidebar-link:hover .nav-icon {
        transform: scale(1.1);
    }

    /* Colors per category aligned to Blueprint Navy / Blue / Emerald / Teal */
    .icon-dashboard { color: #142B4A; }
    .icon-pos { color: #2563EB; }
    .icon-sales { color: #0284C7; }
    .icon-customers { color: #2563EB; }
    .icon-purchases { color: #D97706; }
    .icon-suppliers { color: #B45309; }
    .icon-products { color: #0F766E; }
    .icon-categories { color: #0D9488; }
    .icon-units { color: #0284C7; }
    .icon-transactions { color: #10B981; }
    .icon-fin-categories { color: #059669; }
    .icon-users { color: #142B4A; }
    .icon-settings { color: #0F766E; }
    .icon-audit { color: #64748B; }

    /* Active Icon Colors */
    .sidebar-link.active .nav-icon {
        color: #2563EB !important;
    }

    /* Desktop Collapsible Sidebar State */
    .sidebar-collapsed .sidebar-brand-header {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }
    .sidebar-collapsed .sidebar-brand-text {
        display: none;
    }
    .sidebar-collapsed .sidebar-group-title {
        font-size: 0;
        gap: 0;
        padding: 10px 10px 6px;
    }
    .sidebar-collapsed .sidebar-link {
        justify-content: center;
        gap: 0;
        padding: 10px;
        font-size: 0;
    }
    .sidebar-collapsed .sidebar-link:hover {
        transform: none;
    }
    .sidebar-collapsed .sidebar-link .nav-icon {
        width: 20px;
        height: 20px;
    }
    .sidebar-collapsed .sidebar-link.active::before {
        left: -12px;
    }
</style>

{{-- Desktop Sidebar --}}
<aside class="hidden lg:flex flex-col border-r border-border bg-card text-card-foreground fixed top-0 left-0 h-screen select-none shrink-0 z-30 overflow-hidden transition-all duration-200"
       :class="sidebarCollapsed ? 'w-20 sidebar-collapsed' : 'w-64'">
    {{-- Brand/Logo --}}
    <div class="sidebar-brand-header h-16 flex items-center justify-between px-4 border-b border-border bg-[#142B4A]">
        <a href="{{ route('dashboard') }}" wire:navigate title="{{ brand_name() }}" class="flex items-center gap-2.5 min-w-0">
            <x-application-logo class="shrink-0" style="width:30px;height:30px" />
            <div class="sidebar-brand-text min-w-0">
                <span class="block text-sm font-bold tracking-tight text-white truncate">
                    {{ brand_name() }}
                </span>
                <span class="block text-[10px] font-medium text-sky-200/80 truncate">
                    {{ brand_subtitle() }}
                </span>
            </div>
        </a>
        <button @click="toggleSidebar()"
                x-show="!sidebarCollapsed"
                type="button"
                title="Ciutkan Sidebar"
                class="p-1.5 rounded-lg text-sky-200/80 hover:text-white hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 sidebar-container">

        {{-- Dashboard Group --}}
        @can('dashboard.view')
        <a href="{{ route('dashboard') }}" title="Dashboard"
           wire:navigate class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg class="nav-icon icon-dashboard" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                <rect x="14" y="14" width="7" height="7" rx="1.5" />
                <rect x="3" y="14" width="7" height="7" rx="1.5" />
            </svg>
            Dashboard
        </a>
        @endcan

        {{-- Transaksi Group --}}
        @if(auth()->user()?->can('pos.access') || auth()->user()?->can('sales.view') || auth()->user()?->can('purchases.view'))
        <p class="sidebar-group-title">Transaksi</p>
        
        @can('pos.access')
        <a href="{{ route('sales.create') }}" title="POS (Kasir)"
           wire:navigate class="sidebar-link {{ request()->routeIs('sales.create') ? 'active' : '' }}">
            <svg class="nav-icon icon-pos" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2" />
                <line x1="6" y1="21" x2="18" y2="21" />
                <line x1="12" y1="17" x2="12" y2="21" />
                <circle cx="6" cy="10" r="1" />
                <circle cx="12" cy="10" r="1" />
                <circle cx="18" cy="10" r="1" />
            </svg>
            POS (Kasir)
        </a>

        <a href="{{ route('pos.shifts.index') }}" title="Shift Kasir"
           wire:navigate class="sidebar-link {{ request()->routeIs('pos.shifts.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-pos" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
            </svg>
            Shift Kasir
        </a>
        @endcan

        @can('sales.view')
        <a href="{{ route('sales.index') }}" title="Daftar Penjualan"
           wire:navigate class="sidebar-link {{ request()->routeIs(['sales.index','sales.show']) ? 'active' : '' }}">
            <svg class="nav-icon icon-sales" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
            </svg>
            Daftar Penjualan
        </a>
        @endcan

        @can('purchases.view')
        <a href="{{ route('purchases.index') }}" title="Daftar Pembelian"
           wire:navigate class="sidebar-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            Daftar Pembelian
        </a>
        @endcan
        @endif

        {{-- Kontak Group --}}
        @can('master.view')
        <p class="sidebar-group-title">Kontak & Rekan</p>

        <a href="{{ route('customers.index') }}" title="Pelanggan (Customers)"
           wire:navigate class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-customers" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
            </svg>
            Pelanggan (Customers)
        </a>

        <a href="{{ route('suppliers.index') }}" title="Pemasok (Suppliers)"
           wire:navigate class="sidebar-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-suppliers" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5" />
            </svg>
            Pemasok (Suppliers)
        </a>

        {{-- Katalog Group --}}
        <p class="sidebar-group-title">Katalog Produk</p>

        <a href="{{ route('products.index') }}" title="Produk Barang"
           wire:navigate class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-products" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            Produk Barang
        </a>

        <a href="{{ route('categories.index') }}" title="Kategori Produk"
           wire:navigate class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-categories" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10" />
            </svg>
            Kategori Produk
        </a>

        <a href="{{ route('units.index') }}" title="Satuan Barang"
           wire:navigate class="sidebar-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-units" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0ll-3-9m3 1l3 1m0 0l-3 9a5.002 5.002 0 006.001 0ll-3-9m3 1l3 1m0 0l-3 9a5.002 5.002 0 006.001 0ll-3-9M6 7l6 1m6-1l-6 1m0 0v12" />
            </svg>
            Satuan Barang
        </a>
        @endcan

        {{-- Inventory Group --}}
        @can('inventory.view')
        <p class="sidebar-group-title">Inventory Ledger</p>

        <a href="{{ route('inventory.index') }}" title="Saldo Stok Gudang"
           wire:navigate class="sidebar-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
            <svg class="nav-icon icon-products" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h4" />
            </svg>
            Saldo Stok Gudang
        </a>

        <a href="{{ route('inventory.movements') }}" title="Mutasi Stok (Ledger)"
           wire:navigate class="sidebar-link {{ request()->routeIs('inventory.movements') ? 'active' : '' }}">
            <svg class="nav-icon icon-sales" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
            </svg>
            Mutasi Stok (Ledger)
        </a>

        <a href="{{ route('inventory.opnames') }}" title="Stock Opname"
           wire:navigate class="sidebar-link {{ request()->routeIs('inventory.opnames') ? 'active' : '' }}">
            <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 12l2 2 4-4" />
            </svg>
            Stock Opname
        </a>
        @endcan

        {{-- Order, Work Management & Shipping Group --}}
        @if((auth()->user()?->can('dashboard.view') && auth()->user()?->can('orders.view')) || auth()->user()?->can('work.view') || auth()->user()?->can('shipping.view'))
        <p class="sidebar-group-title">Fulfillment & Work</p>

        @if(auth()->user()?->can('dashboard.view') && auth()->user()?->can('orders.view'))
        <a href="{{ route('orders.index') }}" title="Order Customer"
           wire:navigate class="sidebar-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-sales" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V5a3 3 0 016 0v2" />
            </svg>
            Order Customer
        </a>
        @endif

        @can('work.view')
        <a href="{{ route('work.board') }}" title="Kanban Task Board"
           wire:navigate class="sidebar-link {{ request()->routeIs('work.board') ? 'active' : '' }}">
            <svg class="nav-icon icon-dashboard" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h4v14H4V5zm6 0h4v9h-4V5zm6 0h4v12h-4V5z" />
            </svg>
            Task Board (Kanban)
        </a>

        <a href="{{ route('work.my-tasks') }}" title="Tugas Saya"
           wire:navigate class="sidebar-link {{ request()->routeIs('work.my-tasks') ? 'active' : '' }}">
            <svg class="nav-icon icon-customers" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Tugas Saya
        </a>

        <a href="{{ route('work.tasks') }}" title="Semua Task"
           wire:navigate class="sidebar-link {{ request()->routeIs('work.tasks') ? 'active' : '' }}">
            <svg class="nav-icon icon-settings" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
            </svg>
            Semua Task
        </a>
        @endcan

        @can('shipping.view')
        <a href="{{ route('shipping.index') }}" title="Pengiriman & Resi"
           wire:navigate class="sidebar-link {{ request()->routeIs('shipping.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-products" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3V7zm11 3h4l3 3v4h-7v-7zM7 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z" />
            </svg>
            Pengiriman (Shipping)
        </a>
        @endcan
        @endif

        @can('purchases.view')
        <p class="sidebar-group-title">Procurement</p>

        <a href="{{ route('procurement.index') }}" title="Procurement (PR/PO/GRN)"
           wire:navigate class="sidebar-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-6 9l2 2 4-4" />
            </svg>
            Procurement (PR/PO/GRN)
        </a>
        @endcan

        {{-- Keuangan Group --}}
        @can('finance.view')
        <p class="sidebar-group-title">Keuangan</p>

        <a href="{{ route('finance.transactions.index') }}" title="Transaksi Keuangan"
           wire:navigate class="sidebar-link {{ request()->routeIs('finance.transactions.index') ? 'active' : '' }}">
            <svg class="nav-icon icon-transactions" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Transaksi Keuangan
        </a>

        <a href="{{ route('finance.categories.index') }}" title="Kategori Transaksi"
           wire:navigate class="sidebar-link {{ request()->routeIs('finance.categories.index') ? 'active' : '' }}">
            <svg class="nav-icon icon-fin-categories" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
            </svg>
            Kategori Transaksi
        </a>

        <a href="{{ route('finance.ap.index') }}" title="Hutang Supplier (AP)"
           wire:navigate class="sidebar-link {{ request()->routeIs('finance.ap.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
            </svg>
            Hutang Supplier (AP)
        </a>

        <a href="{{ route('finance.refunds.index') }}" title="Refund Pelanggan"
           wire:navigate class="sidebar-link {{ request()->routeIs('finance.refunds.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-transactions" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            Refunds
        </a>
        @endcan

        @can('reports.view')
        <a href="{{ route('reports.index') }}" title="Laporan (Reports)"
           wire:navigate class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-dashboard" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m4 6V7m4 10v-3M5 20h14" />
            </svg>
            Laporan (Reports)
        </a>
        @endcan

        {{-- Pengaturan Group --}}
        @if(auth()->user()?->can('users.view') || auth()->user()?->can('settings.view') || auth()->user()?->can('audit.view'))
        <p class="sidebar-group-title">Sistem & Pengaturan</p>

        @can('users.view')
        <a href="{{ route('users.index') }}" title="Pengguna (Users)"
           wire:navigate class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-users" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 1 1 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
            </svg>
            Pengguna (Users)
        </a>
        @endcan

        @can('settings.view')
        <a href="{{ route('settings.index') }}" title="Pengaturan"
           wire:navigate class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-settings" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            Pengaturan
        </a>
        @endcan

        @can('audit.view')
        <a href="{{ route('audit.index') }}" title="Audit Log"
           wire:navigate class="sidebar-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
            <svg class="nav-icon icon-audit" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
            </svg>
            Audit Log
        </a>
        @endcan
        @endif

    </nav>
</aside>

{{-- Mobile Sidebar Overlay --}}
<div class="lg:hidden">
    {{-- Backdrop --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/50"
         style="display:none">
    </div>

    {{-- Sliding Panel --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-transform ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-card border-r border-border shadow-xl"
         style="display:none">

        <div class="h-16 flex items-center justify-between px-5 border-b border-border bg-[#142B4A]">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 min-w-0">
                <x-application-logo class="shrink-0" style="width:28px;height:28px" />
                <span class="text-sm font-bold tracking-tight text-white truncate">{{ brand_name() }}</span>
            </a>
            <button @click="sidebarOpen = false" class="p-1.5 rounded-md text-white/80 hover:text-white hover:bg-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 sidebar-container">
            {{-- Dashboard Group --}}
            @can('dashboard.view')
            <a href="{{ route('dashboard') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="nav-icon icon-dashboard" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                    <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                </svg>
                Dashboard
            </a>
            @endcan

            {{-- Transaksi Group --}}
            @if(auth()->user()?->can('pos.access') || auth()->user()?->can('sales.view') || auth()->user()?->can('purchases.view'))
            <p class="sidebar-group-title">Transaksi</p>
            
            @can('pos.access')
            <a href="{{ route('sales.create') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('sales.create') ? 'active' : '' }}">
                <svg class="nav-icon icon-pos" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" />
                    <line x1="6" y1="21" x2="18" y2="21" />
                    <line x1="12" y1="17" x2="12" y2="21" />
                    <circle cx="6" cy="10" r="1" />
                    <circle cx="12" cy="10" r="1" />
                    <circle cx="18" cy="10" r="1" />
                </svg>
                POS (Kasir)
            </a>

            <a href="{{ route('pos.shifts.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('pos.shifts.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-pos" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                </svg>
                Shift Kasir
            </a>
            @endcan

            @can('sales.view')
            <a href="{{ route('sales.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs(['sales.index','sales.show']) ? 'active' : '' }}">
                <svg class="nav-icon icon-sales" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
                </svg>
                Daftar Penjualan
            </a>
            @endcan

            @can('purchases.view')
            <a href="{{ route('purchases.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Daftar Pembelian
            </a>
            @endcan
            @endif

            {{-- Kontak Group --}}
            @can('master.view')
            <p class="sidebar-group-title">Kontak & Rekan</p>

            <a href="{{ route('customers.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-customers" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                </svg>
                Pelanggan (Customers)
            </a>

            <a href="{{ route('suppliers.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-suppliers" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5" />
                </svg>
                Pemasok (Suppliers)
            </a>

            {{-- Katalog Group --}}
            <p class="sidebar-group-title">Katalog Produk</p>

            <a href="{{ route('products.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-products" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Produk Barang
            </a>

            <a href="{{ route('categories.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-categories" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10" />
                </svg>
                Kategori Produk
            </a>

            <a href="{{ route('units.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-units" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0ll-3-9m3 1l3 1m0 0l-3 9a5.002 5.002 0 006.001 0ll-3-9m3 1l3 1m0 0l-3 9a5.002 5.002 0 006.001 0ll-3-9M6 7l6 1m6-1l-6 1m0 0v12" />
                </svg>
                Satuan Barang
            </a>
            @endcan

            {{-- Inventory Group --}}
            @can('inventory.view')
            <p class="sidebar-group-title">Inventory Ledger</p>

            <a href="{{ route('inventory.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                <svg class="nav-icon icon-products" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h4" />
                </svg>
                Saldo Stok Gudang
            </a>

            <a href="{{ route('inventory.movements') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('inventory.movements') ? 'active' : '' }}">
                <svg class="nav-icon icon-sales" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
                </svg>
                Mutasi Stok (Ledger)
            </a>

            <a href="{{ route('inventory.opnames') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('inventory.opnames') ? 'active' : '' }}">
                <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 12l2 2 4-4" />
                </svg>
                Stock Opname
            </a>
            @endcan

            {{-- Order, Work Management & Shipping Group --}}
            @if((auth()->user()?->can('dashboard.view') && auth()->user()?->can('orders.view')) || auth()->user()?->can('work.view') || auth()->user()?->can('shipping.view'))
            <p class="sidebar-group-title">Fulfillment & Work</p>

            @if(auth()->user()?->can('dashboard.view') && auth()->user()?->can('orders.view'))
            <a href="{{ route('orders.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-sales" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V5a3 3 0 016 0v2" />
                </svg>
                Order Customer
            </a>
            @endif

            @can('work.view')
            <a href="{{ route('work.board') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('work.board') ? 'active' : '' }}">
                <svg class="nav-icon icon-dashboard" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h4v14H4V5zm6 0h4v9h-4V5zm6 0h4v12h-4V5z" />
                </svg>
                Task Board (Kanban)
            </a>

            <a href="{{ route('work.my-tasks') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('work.my-tasks') ? 'active' : '' }}">
                <svg class="nav-icon icon-customers" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Tugas Saya
            </a>

            <a href="{{ route('work.tasks') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('work.tasks') ? 'active' : '' }}">
                <svg class="nav-icon icon-settings" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
                </svg>
                Semua Task
            </a>
            @endcan

            @can('shipping.view')
            <a href="{{ route('shipping.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('shipping.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-products" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3V7zm11 3h4l3 3v4h-7v-7zM7 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z" />
                </svg>
                Pengiriman (Shipping)
            </a>
            @endcan
            @endif

            @can('purchases.view')
            <p class="sidebar-group-title">Procurement</p>

            <a href="{{ route('procurement.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-6 9l2 2 4-4" />
                </svg>
                Procurement (PR/PO/GRN)
            </a>
            @endcan

            {{-- Keuangan Group --}}
            @can('finance.view')
            <p class="sidebar-group-title">Keuangan</p>

            <a href="{{ route('finance.transactions.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('finance.transactions.index') ? 'active' : '' }}">
                <svg class="nav-icon icon-transactions" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Transaksi Keuangan
            </a>

            <a href="{{ route('finance.categories.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('finance.categories.index') ? 'active' : '' }}">
                <svg class="nav-icon icon-fin-categories" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
                </svg>
                Kategori Transaksi
            </a>

            <a href="{{ route('finance.ap.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('finance.ap.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-purchases" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                </svg>
                Hutang Supplier (AP)
            </a>

            <a href="{{ route('finance.refunds.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('finance.refunds.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-transactions" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                Refunds
            </a>
            @endcan

            @can('reports.view')
            <a href="{{ route('reports.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-dashboard" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m4 6V7m4 10v-3M5 20h14" />
                </svg>
                Laporan (Reports)
            </a>
            @endcan

            {{-- Pengaturan Group --}}
            @if(auth()->user()?->can('users.view') || auth()->user()?->can('settings.view') || auth()->user()?->can('audit.view'))
            <p class="sidebar-group-title">Sistem & Pengaturan</p>

            @can('users.view')
            <a href="{{ route('users.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-users" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 1 1 0 5.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197M13 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                </svg>
                Pengguna (Users)
            </a>
            @endcan

            @can('settings.view')
            <a href="{{ route('settings.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-settings" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Pengaturan
            </a>
            @endcan

            @can('audit.view')
            <a href="{{ route('audit.index') }}" @click="sidebarOpen=false"
               wire:navigate class="sidebar-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                <svg class="nav-icon icon-audit" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
                </svg>
                Audit Log
            </a>
            @endcan
            @endif
        </nav>
    </div>
</div>
