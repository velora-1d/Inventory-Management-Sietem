<x-app-layout title="Sale Details">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Sale Details') }} #{{ $sale->invoice_number ?: $sale->id }}
            </h2>
            <div class="flex items-center gap-2">
                <x-secondary-button href="{{ route('sales.index') }}">
                    &larr; {{ __('Back to List') }}
                </x-secondary-button>
                <x-primary-button href="{{ route('sales.print', $sale) }}" target="_blank">
                    <x-heroicon-o-printer class="w-4 h-4 mr-2" />
                    {{ __('Print Invoice') }}
                </x-primary-button>
            </div>
        </div>
    </x-slot>

            <!-- Main Info Card -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                <div class="p-6">
                    <!-- Header Info -->
                    <div class="flex items-start justify-between border-b border-gray-100 pb-4 mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Sale Information') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('Details of the sales transaction') }}</p>
                        </div>
                        <div class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200">
                            ID: #{{ $sale->id }}
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Customer -->
                        <x-detail-item label="Customer" :value="$sale->customer->name ?? 'Guest'">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Invoice -->
                        <x-detail-item label="Invoice Number" :value="$sale->invoice_number ?? '-'">
                            <x-heroicon-o-document-text class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Sale Date -->
                        <x-detail-item label="Sale Date" :value="$sale->sale_date->format('d M Y')">
                            <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Payment Method -->
                        <x-detail-item label="Payment Method" :value="$sale->payment_method->label()">
                            <x-heroicon-o-credit-card class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Status -->
                        <div>
                            <label class="text-sm font-medium leading-none text-gray-500">Status</label>
                            <div class="mt-1">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $sale->status->color() }}">
                                    {{ $sale->status->label() }}
                                </span>
                            </div>
                        </div>



                        <!-- Created By -->
                        <x-detail-item label="Created By" :value="$sale->creator->name ?? 'Unknown'">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                        </x-detail-item>
                    </div>

                    <!-- Notes -->
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-gray-500">
                                Notes
                            </label>
                            <div class="bg-gray-50 p-3 rounded-md border border-gray-100">
                                <p class="text-sm text-slate-700 italic leading-relaxed">{{ $sale->notes ?: 'No additional notes.' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table Section -->
                    <div class="mt-6 border-t overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Product</th>
                                    <th class="px-6 py-3">Unit</th>
                                    <th class="px-6 py-3 text-center">Qty</th>
                                    <th class="px-6 py-3 text-right">Price</th>
                                    <th class="px-6 py-3 text-right">Discount</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($sale->items as $item)
                                    <tr class="bg-white hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $item->product->product_code ?? $item->product->sku ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            {{ $item->product->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $item->product->unit->symbol ?? $item->product->unit->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            {{ number_format($item->quantity) }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @money($item->unit_price)
                                        </td>
                                        <td class="px-6 py-4 text-right text-red-500">
                                            {!! $item->discount > 0 ? "- <span>" . format_money($item->discount) . "</span>" : '-' !!}
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium">
                                            @money($item->subtotal)
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 font-bold">
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-right">Subtotal</td>
                                    <td class="px-6 py-4 text-right text-gray-700">
                                        @money($sale->subtotal)
                                    </td>
                                </tr>
                                @if($sale->total_discount > 0)
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-right text-red-600">Total Discount (Items)</td>
                                        <td class="px-6 py-4 text-right text-red-600">
                                            - @money($sale->total_discount - $sale->global_discount)
                                        </td>
                                    </tr>
                                @endif
                                @if($sale->global_discount > 0)
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-right text-red-600">Global Discount (Transaction)</td>
                                        <td class="px-6 py-4 text-right text-red-600">
                                            - @money($sale->global_discount)
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-right">Total</td>
                                    <td class="px-6 py-4 text-right text-sky-600 text-lg">
                                        @money($sale->total)
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-right text-gray-600">Cash Received</td>
                                    <td class="px-6 py-4 text-right text-gray-800">
                                        @money($sale->cash_received)
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-right text-gray-600">Change</td>
                                    <td class="px-6 py-4 text-right text-green-600">
                                        @money($sale->change)
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Workflow -->
            <div x-data="{
                actionUrl: '',
                actionMethod: '',
                modalTitle: '',
                modalMessage: '',
                confirmButtonText: '',
                confirmButtonClass: '',

                confirmAction(url, method, title, message, btnText, btnClass) {
                    this.actionUrl = url;
                    this.actionMethod = method;
                    this.modalTitle = title;
                    this.modalMessage = message;
                    this.confirmButtonText = btnText;
                    this.confirmButtonClass = btnClass;
                    $dispatch('open-modal', { name: 'confirmation-modal' });
                }
            }" class="flex flex-col sm:flex-row justify-end gap-4">

                @if($sale->status === \App\Enums\SaleStatus::PENDING)
                    {{-- Complete / Pay Action --}}
                    <x-primary-button
                        class="!bg-green-600 hover:!bg-green-700 focus:!ring-green-500"
                        @click="confirmAction('{{ route('sales.complete', $sale) }}', 'PATCH', 'Complete Sale', 'Mark this sale as Completed? This confirms payment has been received.', 'Complete Sale', '!bg-green-600 hover:!bg-green-700 focus:!ring-green-500')"
                    >
                        {{ __('Complete Sale') }}
                    </x-primary-button>

                    {{-- Cancel Pending Action (Modal) --}}
                    <div x-data="{ cancelOpen: false, submitting: false }">
                        <x-danger-button @click="cancelOpen = true">
                            {{ __('Cancel Sale') }}
                        </x-danger-button>

                        <!-- Cancel Modal (Premium) -->
                        <div x-show="cancelOpen"
                             style="display: none;"
                             x-on:keydown.escape.window="cancelOpen = false"
                             class="relative z-[200]">

                            <!-- Backdrop -->
                            <div
                                x-show="cancelOpen"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 backdrop-blur-sm bg-black/40 z-[199]"
                                style="display: none;"
                            ></div>

                            <div class="fixed inset-0 z-[200] overflow-y-auto">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div
                                        x-show="cancelOpen"
                                        x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                        x-transition:leave="ease-in duration-200"
                                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                                        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                                        @click.away="cancelOpen = false"
                                    >
                                        <div class="h-1 w-full bg-gradient-to-r from-red-500 via-rose-500 to-red-600"></div>

                                        <div class="p-6">
                                            <div class="flex items-start gap-4">
                                                <div class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-red-50 border-2 border-red-100">
                                                    <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                    </svg>
                                                </div>
                                                <div class="flex-1 pt-0.5">
                                                    <h3 class="text-base font-semibold text-gray-900">{{ __('Cancel Pending Sale') }}</h3>
                                                    <p class="mt-1 text-sm text-gray-500">{{ __('Are you sure you want to cancel this pending sale? Please provide a reason.') }}</p>
                                                </div>
                                            </div>

                                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" @submit="submitting = true" class="mt-5">
                                                @csrf
                                                @method('DELETE')

                                                <div class="space-y-1.5">
                                                    <label for="reason" class="block text-sm font-medium text-gray-700">{{ __('Reason') }} <span class="text-red-500">*</span></label>
                                                    <textarea
                                                        name="reason"
                                                        id="reason"
                                                        rows="3"
                                                        class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-red-400 focus:ring-2 focus:ring-red-300 focus:bg-white transition-all"
                                                        placeholder="Customer changed mind..."
                                                        required
                                                    ></textarea>
                                                </div>

                                                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                                                    <button type="button" @click="cancelOpen = false" x-bind:disabled="submitting"
                                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-all focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50">
                                                        <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Batal
                                                    </button>
                                                    <button type="submit" x-bind:disabled="submitting"
                                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-sm shadow-red-200 active:scale-[0.98] transition-all focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <svg x-show="submitting" class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <svg x-show="!submitting" class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        {{ __('Cancel Sale') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($sale->status === \App\Enums\SaleStatus::COMPLETED)
                    {{-- Cancel Action --}}
                    <x-secondary-button
                        class="text-red-600 hover:bg-red-50 border-red-200"
                        @click="confirmAction('{{ route('sales.destroy', $sale) }}', 'DELETE', 'Cancel Sale', 'Are you sure you want to cancel (VOID) this sale? Stocks will be returned.', 'Yes, Cancel Sale', '!bg-red-600 hover:!bg-red-700 focus:!ring-red-500')"
                    >
                        {{ __('Cancel Sale') }}
                    </x-secondary-button>
                @endif

                @if($sale->status === \App\Enums\SaleStatus::CANCELLED)
                    {{-- Restore Action --}}
                    <x-secondary-button
                        class="bg-gray-800 text-white hover:bg-gray-700 focus:ring-gray-500"
                        @click="confirmAction('{{ route('sales.restore', $sale) }}', 'PATCH', 'Restore Sale', 'Restore this sale to Pending status? You can then complete it again.', 'Restore to Pending', '!bg-gray-800 hover:!bg-gray-700 text-white')"
                    >
                        {{ __('Restore to Pending') }}
                    </x-secondary-button>
                @endif

                <!-- Shared Confirmation Modal (Premium) -->
                <div
                    x-data="{ modalShow: false, submitting: false }"
                    x-on:open-modal.window="if ($event.detail.name === 'confirmation-modal') modalShow = true"
                    x-on:close-modal.window="if ($event.detail.name === 'confirmation-modal') modalShow = false"
                    x-on:keydown.escape.window="modalShow = false; submitting = false"
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
                                <div class="h-1 w-full bg-gradient-to-r from-sky-500 to-indigo-600"></div>

                                <div class="p-6">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-amber-50 border-2 border-amber-100">
                                            <svg class="w-6 h-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 pt-0.5">
                                            <h3 class="text-base font-semibold text-gray-900" x-text="modalTitle"></h3>
                                            <p class="mt-1.5 text-sm text-gray-500 leading-relaxed" x-text="modalMessage"></p>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                                        <button
                                            type="button"
                                            @click="modalShow = false; submitting = false"
                                            x-bind:disabled="submitting"
                                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50"
                                        >
                                            <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Batal
                                        </button>

                                        <form :action="actionUrl" method="POST" @submit="submitting = true">
                                            @csrf
                                            <input type="hidden" name="_method" :value="actionMethod">
                                            <button
                                                type="submit"
                                                x-bind:disabled="submitting"
                                                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 shadow-sm shadow-sky-200 active:scale-[0.98] transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed"
                                            >
                                                <svg x-show="submitting" class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <svg x-show="!submitting" class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                                <span x-text="confirmButtonText"></span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
</x-app-layout>
