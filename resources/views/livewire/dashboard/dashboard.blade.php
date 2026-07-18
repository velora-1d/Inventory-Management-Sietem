<div>
    <!-- Premium Dashboard Styles -->
    <style>
        /* Backdrop Blur & Glossmorphism */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(51, 65, 85, 0.5);
        }

        /* Ambient Glow Lights */
        .glow-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            position: relative;
        }
        .glow-dot::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            background: inherit;
            opacity: 0.4;
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {
            0% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(2); opacity: 0; }
            100% { transform: scale(1); opacity: 0.5; }
        }

        /* Hover Transitions */
        .premium-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .premium-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08);
            border-color: rgba(14, 165, 233, 0.35);
        }

        /* Progress Bar Animation */
        .progress-bar-fill {
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Custom Scrollbar for inner boxes */
        .custom-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.3);
            border-radius: 99px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.5);
        }

        /* Print Media styling */
        @media print {
            @page { size: landscape; margin: 1cm; }
            body { background-color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print\:hidden { display: none !important; }
            .glass-panel { background: white !important; border: 1px solid #cbd5e1 !important; box-shadow: none !important; backdrop-filter: none !important; }
            .premium-card { transform: none !important; box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
            .grid { gap: 1rem !important; }
            .break-inside-avoid { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>

    <div class="space-y-6">
        <!-- Glass Control & Filter Panel -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 glass-panel p-5 rounded-2xl shadow-sm">
            <div>
                <div class="flex items-center gap-2">
                    <span class="glow-dot bg-sky-500"></span>
                    <h2 class="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-100">Overview Dashboard</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Real-time business performance analytics and key metrics overview.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <!-- Period Selector -->
                <div class="relative w-full sm:w-[180px]">
                    <select wire:model.live="dateFilter" class="w-full h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-medium text-slate-700 dark:text-slate-200 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        @foreach(\App\Enums\DatePeriod::cases() as $period)
                            <option value="{{ $period->value }}">{{ $period->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Custom Date Range (Flatpickr) -->
                <div x-show="$wire.dateFilter === 'custom'" x-transition 
                     class="w-full sm:w-auto flex items-center"
                     x-data="{
                         init() {
                             flatpickr(this.$refs.picker, {
                                 mode: 'range',
                                 dateFormat: 'Y-m-d',
                                 defaultDate: [this.$wire.customStartDate, this.$wire.customEndDate],
                                 onChange: (selectedDates, dateStr, instance) => {
                                     if (selectedDates.length === 2) {
                                         this.$wire.updateCustomRange(
                                             instance.formatDate(selectedDates[0], 'Y-m-d'),
                                             instance.formatDate(selectedDates[1], 'Y-m-d')
                                         );
                                     }
                                 }
                             });
                         }
                     }"
                >
                    <input x-ref="picker" type="text" 
                           class="h-10 w-full sm:w-[230px] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs text-slate-700 dark:text-slate-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500" 
                           placeholder="Pilih rentang tanggal...">
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <!-- Refresh Button -->
                    <button wire:click="$refresh" 
                            class="print:hidden inline-flex items-center justify-center rounded-xl text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all h-10 w-10 shadow-sm">
                        <x-heroicon-o-arrow-path wire:loading.class="animate-spin" class="h-4 w-4" />
                    </button>
                    
                    <!-- Print Button -->
                    <button onclick="window.print()" 
                            class="print:hidden inline-flex items-center justify-center rounded-xl text-xs font-semibold bg-sky-600 hover:bg-sky-500 text-white transition-all h-10 px-4 gap-2 shadow-sm shadow-sky-500/10">
                        <x-heroicon-o-printer class="h-4 w-4" />
                        <span>Cetak Laporan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Metric Summary Cards Grid -->
        <div class="grid gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Total Sales -->
            <div class="glass-panel premium-card rounded-2xl p-5 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-sky-500/5 to-transparent rounded-full -mr-4 -mt-4"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Penjualan</span>
                    <div class="p-2 bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 rounded-xl">
                        <x-heroicon-o-banknotes class="h-5 w-5" />
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-2xl font-black tracking-tight text-slate-800 dark:text-slate-100">
                        @money($stats['total_sales'] ?? 0)
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>{{ $stats['sales_count'] ?? 0 }} Transaksi Berhasil</span>
                    </div>
                </div>
            </div>

            <!-- 2. Gross Profit -->
            <div class="glass-panel premium-card rounded-2xl p-5 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-teal-500/5 to-transparent rounded-full -mr-4 -mt-4"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Laba Kotor</span>
                    <div class="p-2 bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 rounded-xl">
                        <x-heroicon-o-arrow-trending-up class="h-5 w-5" />
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-2xl font-black tracking-tight text-slate-800 dark:text-slate-100">
                        @money($stats['gross_profit'] ?? 0)
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Estimasi berdasarkan HPP produk
                    </div>
                </div>
            </div>

            <!-- 3. Net Cash Flow -->
            @php
                $isPositiveCash = ($stats['net_cash_flow'] ?? 0) >= 0;
            @endphp
            <div class="glass-panel premium-card rounded-2xl p-5 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-emerald-500/5 to-transparent rounded-full -mr-4 -mt-4"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Arus Kas Bersih</span>
                    <div class="p-2 {{ $isPositiveCash ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600' }} rounded-xl">
                        <x-heroicon-o-currency-dollar class="h-5 w-5" />
                    </div>
                </div>
                <div class="space-y-1.5">
                    <div class="text-2xl font-black tracking-tight {{ $isPositiveCash ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        @money($stats['net_cash_flow'] ?? 0)
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-medium border-t border-slate-100 dark:border-slate-800/60 pt-2 mt-1">
                        <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-0.5" title="Total Pemasukan">
                            <x-heroicon-s-arrow-up class="w-3.5 h-3.5" /> @money($stats['income'] ?? 0)
                        </span>
                        <span class="text-rose-600 dark:text-rose-400 flex items-center gap-0.5" title="Total Pengeluaran">
                            <x-heroicon-s-arrow-down class="w-3.5 h-3.5" /> @money($stats['expense'] ?? 0)
                        </span>
                    </div>
                </div>
            </div>

            <!-- 4. Low Stock Alert -->
            @php
                $lowStockCount = count($lowStockProducts);
                $hasWarning = $lowStockCount > 0;
            @endphp
            <div class="glass-panel premium-card rounded-2xl p-5 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-amber-500/5 to-transparent rounded-full -mr-4 -mt-4"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Peringatan Stok</span>
                    <div class="p-2 {{ $hasWarning ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600' }} rounded-xl">
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-2xl font-black tracking-tight text-slate-800 dark:text-slate-100">
                        {{ $lowStockCount }} <span class="text-sm font-semibold text-slate-500">Produk</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                        @if($hasWarning)
                            <span class="glow-dot bg-amber-500"></span>
                            <span class="text-amber-600 dark:text-amber-400 font-medium">Stok menipis, segera reorder</span>
                        @else
                            <span class="glow-dot bg-emerald-500"></span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">Semua stok produk aman</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Layout Grid -->
        <div class="grid gap-6 grid-cols-1 lg:grid-cols-3">
            <!-- Sales Trend Chart (Area) -->
            <div class="lg:col-span-2 glass-panel rounded-2xl p-5 shadow-sm break-inside-avoid">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60 mb-4">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">Tren Penjualan Harian</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Grafik volume penjualan produk berdasarkan waktu.</p>
                    </div>
                    <span class="px-2 py-0.5 bg-sky-50 dark:bg-sky-950/40 text-[10px] font-semibold text-sky-600 dark:text-sky-400 rounded-md">IDR</span>
                </div>
                <div wire:ignore class="relative w-full">
                    <div id="salesChart" class="w-full h-[270px]"></div>
                </div>
            </div>

            <!-- Cash Flow Chart (Income vs Expense) -->
            <div class="glass-panel rounded-2xl p-5 shadow-sm break-inside-avoid">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60 mb-4">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">Pemasukan vs Pengeluaran</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Komparasi keuangan tunai masuk & keluar.</p>
                    </div>
                </div>
                <div wire:ignore class="relative w-full">
                    <div id="cashFlowChart" class="w-full h-[270px]"></div>
                </div>
            </div>
        </div>

        <!-- Secondary Information Section Grid -->
        <div class="grid gap-6 grid-cols-1 lg:grid-cols-3">
            <!-- Recent Sales Log Table -->
            <div class="lg:col-span-2 glass-panel rounded-2xl p-5 shadow-sm break-inside-avoid flex flex-col">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60 mb-4">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">Penjualan Terbaru</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Daftar transaksi kasir terupdate saat ini.</p>
                    </div>
                    <a href="{{ route('sales.index') }}" class="text-[11px] font-semibold text-sky-600 dark:text-sky-400 hover:underline">Lihat Semua</a>
                </div>
                
                <div class="relative w-full overflow-auto max-h-[320px] custom-scroll">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800/60 pb-2">
                                <th class="text-left font-semibold pb-3">No. Invoice & Pelanggan</th>
                                <th class="text-right font-semibold pb-3">Jumlah Penjualan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50 dark:divide-slate-800/30">
                            @forelse($recentSales as $sale)
                                <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-900/30 transition-colors">
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-3">
                                            <!-- Initials Avatar -->
                                            @php
                                                $custName = $sale['customer']['name'] ?? 'Guest';
                                                $words = explode(' ', $custName);
                                                $initials = strtoupper(substr($words[0], 0, 1) . (count($words) > 1 ? substr($words[1], 0, 1) : ''));
                                            @endphp
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-[10px]">
                                                {{ $initials }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-700 dark:text-slate-200 block">{{ $sale['invoice_number'] }}</span>
                                                <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ $custName }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 text-right">
                                        <div class="font-black text-emerald-600 dark:text-emerald-400">
                                            @money($sale['total'])
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-8 text-center text-slate-400 dark:text-slate-500">Belum ada transaksi penjualan terekam.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expense Breakdown Chart (Donut) -->
            <div class="glass-panel rounded-2xl p-5 shadow-sm break-inside-avoid flex flex-col">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60 mb-4">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">Distribusi Pengeluaran</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Persentase pengeluaran berdasarkan kategori.</p>
                    </div>
                </div>
                <div wire:ignore class="relative w-full flex-1 flex items-center justify-center">
                    <div id="expenseChart" class="w-full h-[260px]"></div>
                </div>
            </div>
        </div>

        <!-- Lower Grid: Top Selling Products & Customers -->
        <div class="grid gap-6 grid-cols-1 md:grid-cols-2">
            <!-- Top Selling Products with relative Performance Bars -->
            <div class="glass-panel rounded-2xl p-5 shadow-sm break-inside-avoid">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60 mb-4">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">Produk Terlaris</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Produk paling banyak terjual.</p>
                    </div>
                </div>
                
                <div class="space-y-4 pt-1 max-h-[320px] overflow-y-auto custom-scroll pr-1">
                    @php
                        $maxSold = count($topProducts) > 0 ? max(array_column($topProducts, 'total_sold')) : 1;
                    @endphp
                    @forelse($topProducts as $product)
                        @php
                            $percentage = ($product['total_sold'] / $maxSold) * 100;
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <div class="space-y-0.5 flex-1 min-w-0 pr-4">
                                    <p class="font-bold text-slate-700 dark:text-slate-200 truncate" title="{{ $product['product_name'] }}">{{ $product['product_name'] }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ $product['sku'] }}</p>
                                </div>
                                <div class="font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800/80 px-2 py-0.5 rounded-lg text-[10px]">
                                    {{ $product['total_sold'] }} <span class="text-slate-400 font-normal">terjual</span>
                                </div>
                            </div>
                            <!-- Relative performance bar -->
                            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800/80 rounded-full overflow-hidden">
                                <div class="h-full bg-sky-500 rounded-full progress-bar-fill" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 dark:text-slate-500 text-center py-8">Belum ada data produk terjual.</p>
                    @endforelse
                </div>
            </div>

            <!-- Top Customers with revenue badge -->
            <div class="glass-panel rounded-2xl p-5 shadow-sm break-inside-avoid">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60 mb-4">
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">Pelanggan Utama</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Kontributor pendapatan penjualan terbesar.</p>
                    </div>
                </div>
                
                <div class="space-y-4 pt-1 max-h-[320px] overflow-y-auto custom-scroll pr-1">
                    @forelse($topCustomers as $customer)
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-3 min-w-0 flex-1 pr-4">
                                <!-- Initials Avatar with random slate gradients -->
                                @php
                                    $custName = $customer['customer_name'];
                                    $words = explode(' ', $custName);
                                    $initials = strtoupper(substr($words[0], 0, 1) . (count($words) > 1 ? substr($words[1], 0, 1) : ''));
                                @endphp
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-[10px] shrink-0">
                                    {{ $initials }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-700 dark:text-slate-200 truncate" title="{{ $custName }}">{{ $custName }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ $customer['phone'] ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="font-bold text-[10px] text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 px-2 py-1 rounded-lg whitespace-nowrap shadow-sm">
                                @money($customer['total_spent'])
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 dark:text-slate-500 text-center py-8">Belum ada data pelanggan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- ApexCharts Setup Script -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            let salesChart = null;
            let cashFlowChart = null;
            
            const currencySymbol = "{{ \App\Models\Setting::get('currency_symbol', 'Rp') }}";
            const currencyPosition = "{{ \App\Models\Setting::get('currency_position', 'left') }}";

            const formatMoney = (val) => {
                let num = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(val);
                return currencyPosition === 'left' ? currencySymbol + ' ' + num : num + ' ' + currencySymbol;
            };

            const initCharts = (data) => {
                // Sales Trend Chart (Area)
                const salesOptions = {
                    series: [{
                        name: 'Penjualan',
                        data: data.sales.data
                    }],
                    chart: {
                        type: 'area',
                        height: 270,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        parentHeightOffset: 0
                    },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
                    xaxis: {
                        categories: data.sales.labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: { 
                                colors: 'rgba(156, 163, 175, 0.7)',
                                fontSize: '10px',
                                fontFamily: 'inherit' 
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: { 
                                colors: 'rgba(156, 163, 175, 0.7)',
                                fontSize: '10px',
                                fontFamily: 'inherit'
                            },
                            formatter: (val) => {
                                 if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                 if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
                                 return val;
                            }
                        }
                    },
                    grid: {
                        borderColor: 'rgba(156, 163, 175, 0.1)',
                        strokeDashArray: 4,
                        padding: {
                            left: 10,
                            right: 0,
                            top: -15,
                            bottom: -10
                        }
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.35,
                            opacityTo: 0.02,
                            stops: [0, 90, 100]
                        }
                    },
                    colors: ['#0ea5e9'], // Sky 500
                    markers: {
                        size: 0,
                        hover: { size: 5 }
                    },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function (val) {
                                 return formatMoney(val);
                            }
                        }
                    }
                };

                // Cash Flow Chart (Bar)
                const cashFlowOptions = {
                    series: [{
                        name: 'Pemasukan',
                        data: data.cashFlow.income
                    }, {
                        name: 'Pengeluaran',
                        data: data.cashFlow.expense
                    }],
                    chart: {
                        type: 'bar',
                        height: 270,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        parentHeightOffset: 0
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 5
                        },
                    },
                    dataLabels: { enabled: false },
                    stroke: { show: true, width: 3, colors: ['transparent'] },
                    xaxis: {
                        categories: data.cashFlow.labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: { 
                                colors: 'rgba(156, 163, 175, 0.7)',
                                fontSize: '10px',
                                fontFamily: 'inherit'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: { 
                                colors: 'rgba(156, 163, 175, 0.7)',
                                fontSize: '10px',
                                fontFamily: 'inherit'
                            },
                            formatter: (val) => {
                                 if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                 if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
                                 return val;
                            }
                        }
                    },
                    grid: {
                        borderColor: 'rgba(156, 163, 175, 0.1)',
                        strokeDashArray: 4,
                        padding: {
                            left: 10,
                            right: 0,
                            top: -15,
                            bottom: -10
                        }
                    },
                    colors: ['#10b981', '#f43f5e'], // Emerald 500, Rose 500 (No purple/indigo)
                    fill: { opacity: 0.95 },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function (val) {
                                 return formatMoney(val);
                            }
                        }
                    }
                };

                // Expense Breakdown Chart (Donut)
                const hasExpenseData = data.expense.series && data.expense.series.length > 0;
                const expenseOptions = {
                    series: hasExpenseData ? data.expense.series.map(Number) : [1],
                    labels: hasExpenseData ? data.expense.labels : ['Belum ada Pengeluaran'],
                    chart: {
                        type: 'donut',
                        height: 260,
                        fontFamily: 'inherit',
                        parentHeightOffset: 0
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '72%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        fontSize: '11px',
                                        color: 'rgba(156, 163, 175, 0.8)',
                                        offsetY: -8
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '14px',
                                        fontWeight: 'bold',
                                        color: '#334155',
                                        offsetY: 6,
                                        formatter: function (val) {
                                            if (!hasExpenseData) return '-';
                                            return formatMoney(val);
                                        }
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        color: 'rgba(156, 163, 175, 0.8)',
                                        formatter: function (w) {
                                            if (!hasExpenseData) return '-';
                                            const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                            return formatMoney(sum);
                                        }
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    // Curated tech colors: Sky blue, Teal, Emerald, Amber, Rose, Cyan - No purple!
                    colors: hasExpenseData ? ['#f43f5e', '#f97316', '#eab308', '#10b981', '#06b6d4', '#0ea5e9'] : ['#e2e8f0'],
                    stroke: { width: 3, colors: ['#fff'] },
                    tooltip: {
                        enabled: hasExpenseData,
                        theme: 'light',
                        y: {
                            formatter: function (val) {
                                 return formatMoney(val);
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        offsetY: 0,
                        fontSize: '11px',
                        fontFamily: 'inherit',
                        labels: {
                            colors: '#64748b'
                        },
                        markers: {
                            width: 8,
                            height: 8,
                            radius: 99
                        }
                    }
                };

                if (salesChart) salesChart.destroy();
                if (cashFlowChart) cashFlowChart.destroy();
                if (window.expenseChartInst) window.expenseChartInst.destroy();

                salesChart = new ApexCharts(document.querySelector("#salesChart"), salesOptions);
                salesChart.render();

                cashFlowChart = new ApexCharts(document.querySelector("#cashFlowChart"), cashFlowOptions);
                cashFlowChart.render();
                
                window.expenseChartInst = new ApexCharts(document.querySelector("#expenseChart"), expenseOptions);
                window.expenseChartInst.render();
            };

            // Initial Load
            initCharts({
                sales: @json($salesChart),
                cashFlow: @json($cashFlowChart),
                expense: @json($expenseChart)
            });

            // Listen for server-side updates
            Livewire.on('stats-updated', (data) => {
                 initCharts(data[0]);
            });
        });
    </script>
</div>
