@props(['title' => ''])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}{{ !empty($title) ? ' | ' . $title : '' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            .nav-icon { width:16px; height:16px; flex-shrink:0; display:block; }
            .nav-chevron { display:inline-flex; align-items:center; transition:transform 0.2s ease; }
            .nav-chevron-open { transform:rotate(180deg); }
        </style>
    </head>
    <body class="font-sans antialiased bg-background text-foreground" x-data="{ sidebarOpen: false }">
        <div class="min-h-screen bg-background flex">
            <!-- Sidebar -->
            @include('layouts.navigation')

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
                <!-- Top Navbar -->
                <header class="h-16 border-b border-slate-100 bg-white/85 backdrop-blur-md flex items-center justify-between px-6 sticky top-0 z-40 shadow-sm">
                    <!-- Left Section: Mobile Menu & Greeting -->
                    <div class="flex items-center gap-4">
                        <!-- Mobile Menu Button -->
                        <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl hover:bg-slate-100 text-slate-600 transition-colors">
                            <x-heroicon-o-bars-3 class="h-5 w-5" />
                        </button>

                        <!-- Dynamic Greeting -->
                        <div class="hidden lg:flex items-center gap-2">
                            <span class="text-sm font-bold text-slate-700">
                                @php
                                    $hour = date('H');
                                    if ($hour < 12) {
                                        $greeting = 'Selamat Pagi';
                                    } elseif ($hour < 15) {
                                        $greeting = 'Selamat Siang';
                                    } elseif ($hour < 18) {
                                        $greeting = 'Selamat Sore';
                                    } else {
                                        $greeting = 'Selamat Malam';
                                    }
                                @endphp
                                {{ $greeting }}, <span class="text-sky-600 font-extrabold">{{ Auth::user()->name }}</span>!
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100 uppercase tracking-wider">
                                POS Active
                            </span>
                        </div>
                    </div>

                    <!-- Right Section: Clock, POS Shortcut, Notification & Profile -->
                    <div class="flex items-center gap-6">
                        <!-- Live Digital Clock -->
                        <div x-data="{ time: '' }" x-init="setInterval(() => {
                            const d = new Date();
                            time = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        }, 1000); time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });" class="hidden sm:flex items-center gap-2 text-slate-500 font-mono text-xs bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl shadow-sm">
                            <svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-text="time"></span>
                        </div>

                        <!-- POS Cashier Quick Shortcut -->
                        <a href="{{ route('sales.create') }}" class="flex items-center gap-2 text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 px-4 py-2 rounded-xl shadow-md hover:shadow-lg hover:scale-105 active:scale-95 transition-all duration-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>Transaksi Baru</span>
                        </a>

                        <!-- Notification Bell -->
                        <div class="relative text-slate-400 hover:text-slate-600 transition-colors cursor-pointer group">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white"></span>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="flex items-center gap-3 pl-3 border-l border-slate-100">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center gap-2 hover:bg-slate-50 px-2 py-1.5 rounded-xl transition-all group focus:outline-none">
                                        <x-avatar :name="Auth::user()->name" />
                                        <span class="hidden md:inline-flex text-xs font-semibold text-slate-600 group-hover:text-slate-800 transition-colors">{{ Auth::user()->name }}</span>
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-transform duration-200 group-focus:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.index')" :active="request()->routeIs('profile.*')">
                                        {{ __('Profile') }}
                                    </x-dropdown-link>

                                    <x-dropdown-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
                                        {{ __('Settings') }}
                                    </x-dropdown-link>

                                    <!-- Authentication -->
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault();
                                                            this.closest('form').submit();">
                                            {{ __('Log Out') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </header>

                <div class="flex-1 p-4 md:p-6">
                    <!-- Page Heading -->
                    @isset($header)
                        <div class="bg-gradient-to-r from-white to-slate-50/50 border border-slate-100 px-5 py-4 rounded-2xl shadow-sm mb-6">
                            {{ $header }}
                        </div>
                    @endisset

                    <!-- Page Content -->
                    <main>
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
        <x-toaster />
        <livewire:components.delete-modal />
        @livewireScripts
        @stack('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('open-print-window', (event) => {
                    let url = event.url;
                    if (url) {
                        // Check if current page has 'filters[date_period]' in URL
                        const params = new URLSearchParams(window.location.search);
                        const periodFilter = params.get('filters[date_period]');

                        // If found and not already in target URL, append it
                        if (periodFilter && !url.includes('period=')) {
                            url += (url.includes('?') ? '&' : '?') + 'period=' + periodFilter;
                        }

                        window.open(url, '_blank');
                    }
                });
            });

            // Global Currency Formatter
            window.currencySymbol = "{{ \App\Models\Setting::get('currency_symbol', 'Rp') }}";
            window.currencyPosition = "{{ \App\Models\Setting::get('currency_position', 'left') }}";
            window.currencyFraction = parseInt("{{ \App\Models\Setting::get('currency_fraction_digits', 0) }}");
            window.thousandSeparator = "{{ \App\Models\Setting::get('currency_thousand_separator', '.') }}";
            window.decimalSeparator = "{{ \App\Models\Setting::get('currency_decimal_separator', ',') }}";

            window.formatMoney = function(val) {
                let amount = parseFloat(val) || 0;
                let isNegative = amount < 0;
                amount = Math.abs(amount);

                // Calculate fraction
                let strAmount = amount.toFixed(window.currencyFraction);
                let parts = strAmount.split('.');
                let integerPart = parts[0];
                let decimalPart = parts.length > 1 ? window.decimalSeparator + parts[1] : '';

                // Add thousand separators
                let rgx = /(\d+)(\d{3})/;
                while (rgx.test(integerPart)) {
                    integerPart = integerPart.replace(rgx, '$1' + window.thousandSeparator + '$2');
                }

                let num = integerPart + decimalPart;
                if (isNegative) num = '-' + num;

                return window.currencyPosition === 'left' ? window.currencySymbol + ' ' + num : num + ' ' + window.currencySymbol;
            };

            // Alias for consistency across older components
            window.formatCurrency = window.formatMoney;
        </script>

    </body>
</html>
