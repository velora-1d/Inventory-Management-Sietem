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
                <header class="h-16 border-b border-border flex items-center justify-between lg:justify-end px-6 bg-background sticky top-0 z-40">
                    <!-- Mobile Menu Button -->
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-md hover:bg-muted text-foreground">
                        <x-heroicon-o-bars-3 class="h-6 w-6" />
                    </button>

                    <!-- User Dropdown & Settings -->
                    <div class="flex items-center gap-4">
                        <span class="hidden md:inline-flex text-sm font-medium">{{ Auth::user()->name }}</span>
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-full focus:outline-none transition-colors">
                                    <x-avatar :name="Auth::user()->name" />
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
                </header>

                <div class="flex-1 p-4 md:p-6">
                    <!-- Page Heading -->
                    @isset($header)
                        <div class="mb-6">
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
