<x-app-layout title="Purchase Details">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-foreground leading-tight">
                {{ __('Purchase Details') }} #{{ $purchase->invoice_number ?: $purchase->id }}
            </h2>
            <div class="flex items-center gap-2">
                <x-secondary-button href="{{ route('purchases.index') }}">
                    &larr; {{ __('Back to List') }}
                </x-secondary-button>
                @if(in_array($purchase->status, [\App\Enums\PurchaseStatus::DRAFT, \App\Enums\PurchaseStatus::ORDERED]))
                    <x-secondary-button href="{{ route('purchases.edit', $purchase) }}">
                        {{ __('Edit') }}
                    </x-secondary-button>
                @endif
            </div>
        </div>
    </x-slot>

            <!-- Main Info Card -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                <div class="p-6">
                    <!-- Header Info -->
                    <div class="flex items-start justify-between border-b border-gray-100 pb-4 mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Purchase Information') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('Details of the purchase transaction') }}</p>
                        </div>
                        <div class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200">
                            ID: #{{ $purchase->id }}
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Supplier -->
                        <x-detail-item label="Supplier" :value="$purchase->supplier->name">
                            <x-heroicon-o-building-storefront class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Invoice -->
                        <x-detail-item label="Invoice Number" :value="$purchase->invoice_number ?? '-'">
                            <x-heroicon-o-document-text class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Purchase Date -->
                        <x-detail-item label="Purchase Date" :value="$purchase->purchase_date->format('d M Y')">
                            <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Due Date -->
                        <x-detail-item label="Due Date" :value="$purchase->due_date ? $purchase->due_date->format('d M Y') : '-'">
                            <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Status -->
                        <div>
                            <label class="text-sm font-medium leading-none text-gray-500">Status</label>
                            <div class="mt-1">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $purchase->status->color() }}">
                                    {{ $purchase->status->label() }}
                                </span>
                            </div>
                        </div>

                        <!-- Total Amount -->
                        <x-detail-item label="Total Amount" :value="format_money($purchase->total)">
                            <x-heroicon-o-banknotes class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Created By -->
                        <x-detail-item label="Created By" :value="$purchase->creator->name ?? 'Unknown'">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                        </x-detail-item>

                        <!-- Proof Image -->
                        @if($purchase->proof_image)
                            <div>
                                <label class="text-sm font-medium leading-none text-gray-500">Proof of Receipt</label>
                                <div class="mt-1">
                                    <a href="{{ Storage::url($purchase->proof_image) }}" target="_blank" class="text-sky-600 hover:underline text-sm flex items-center gap-1">
                                        <x-heroicon-o-paper-clip class="w-4 h-4" />
                                        View Image
                                    </a>
                                </div>
                            </div>
                        @else
                            <x-detail-item label="Proof of Receipt" value="-" />
                        @endif
                    </div>

                    <!-- Notes -->
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <div class="space-y-1">
                            <label class="text-sm font-medium leading-none text-gray-500">
                                Notes
                            </label>
                            <div class="bg-gray-50 p-3 rounded-md border border-gray-100">
                                <p class="text-sm text-slate-700 italic leading-relaxed">{{ $purchase->notes ?: 'No additional notes.' }}</p>
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
                                    <th class="px-6 py-3 text-center">Quantity</th>
                                    <th class="px-6 py-3 text-right">Buying Price</th>
                                    <th class="px-6 py-3 text-right">Selling Price</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($purchase->items as $item)
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
                                        <td class="px-6 py-4 text-right">
                                            @money($item->selling_price)
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium">
                                            @money($item->subtotal)
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 font-bold">
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-right">Total</td>
                                    <td class="px-6 py-4 text-right text-sky-600 text-lg">
                                        @money($purchase->total)
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
                    // Manual DOM manipulation to ensure reliability
                    document.getElementById('confirmation-form').action = url;
                    document.getElementById('confirmation-method').value = method;

                    this.modalTitle = title;
                    this.modalMessage = message;
                    this.confirmButtonText = btnText;
                    this.confirmButtonClass = btnClass;
                    $dispatch('open-modal', { name: 'confirmation-modal' });
                }
            }" class="flex flex-col sm:flex-row justify-end gap-4">

                @if($purchase->status === \App\Enums\PurchaseStatus::DRAFT)

                    {{-- Delete Action --}}
                    <x-danger-button
                        type="button"
                        @click="confirmAction('{{ route('purchases.destroy', $purchase) }}', 'DELETE', 'Delete Draft', 'Are you sure you want to delete this draft? This action cannot be undone.', 'Delete Draft', '!bg-red-600 hover:!bg-red-700 focus:!ring-red-500')"
                    >
                        {{ __('Delete Draft') }}
                    </x-danger-button>

                    {{-- Order Action --}}
                    <x-primary-button
                        type="button"
                        class="!bg-sky-600 hover:!bg-sky-700 focus:!ring-sky-500"
                        @click="confirmAction('{{ route('purchases.mark-ordered', $purchase) }}', 'PATCH', 'Mark as Ordered', 'Are you sure you want to mark this purchase as ordered? The stock will not be updated until items are received.', 'Mark as Ordered', '!bg-sky-600 hover:!bg-sky-700 focus:!ring-sky-500')"
                    >
                        {{ __('Mark as Ordered') }}
                    </x-primary-button>

                @elseif($purchase->status === \App\Enums\PurchaseStatus::ORDERED)

                    {{-- Cancel Action --}}
                    <x-secondary-button
                        type="button"
                        class="text-red-600 hover:bg-red-50 border-red-200"
                        @click="confirmAction('{{ route('purchases.cancel', $purchase) }}', 'PATCH', 'Cancel Order', 'Are you sure you want to cancel this order?', 'Cancel Order', '!bg-red-600 hover:!bg-red-700 focus:!ring-red-500')"
                    >
                        {{ __('Cancel Order') }}
                    </x-secondary-button>

                    {{-- Receive Action Trigger (Modal) --}}
                    <div x-data="{ open: @if($errors->has('invoice_number') || $errors->has('proof_image')) true @else false @endif }">
                        <x-primary-button @click="open = true" class="!bg-green-600 hover:!bg-green-700 focus:!ring-green-500">
                            <x-heroicon-o-check-circle class="w-5 h-5 mr-1" />
                            {{ __('Receive Items') }}
                        </x-primary-button>

                        <!-- Receive Items Modal (Premium) -->
                        <div x-show="open"
                             style="display: none;"
                             x-on:keydown.escape.window="open = false"
                             class="relative z-[200]">

                            <!-- Backdrop -->
                            <div
                                x-show="open"
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
                                        x-show="open"
                                        x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                        x-transition:leave="ease-in duration-200"
                                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                                        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                                        @click.away="open = false"
                                    >
                                        <div class="h-1 w-full bg-gradient-to-r from-emerald-500 to-green-600"></div>

                                        <div class="p-6">
                                            <div class="flex items-center gap-3 mb-5">
                                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-50 border-2 border-emerald-100">
                                                    <svg class="w-5 h-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-base font-semibold text-gray-900">Receive Purchase</h3>
                                                    <p class="text-sm text-gray-500">#{{ $purchase->invoice_number ?? $purchase->id }}</p>
                                                </div>
                                            </div>

                                            <form
                                                action="{{ route('purchases.mark-received', $purchase) }}"
                                                method="POST"
                                                enctype="multipart/form-data"
                                                x-data="{ submitting: false }"
                                                @submit="submitting = true"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <div class="space-y-4">
                                                    <!-- Invoice Section -->
                                                    @if($purchase->invoice_number)
                                                        <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-100">
                                                            <span class="block text-xs font-medium text-emerald-600 uppercase tracking-wide">Invoice Number</span>
                                                            <span class="text-sm font-semibold text-gray-900">{{ $purchase->invoice_number }}</span>
                                                        </div>
                                                    @else
                                                        <div class="space-y-1.5">
                                                            <label for="invoice_number" class="block text-sm font-medium text-gray-700">Final Invoice Number <span class="text-red-500">*</span></label>
                                                            <input
                                                                type="text"
                                                                id="invoice_number"
                                                                name="invoice_number"
                                                                value="{{ old('invoice_number') }}"
                                                                required
                                                                placeholder="INV...."
                                                                class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-300 focus:bg-white transition-all"
                                                            />
                                                            <x-input-error :messages="$errors->get('invoice_number')" class="mt-1" />
                                                        </div>
                                                    @endif

                                                    <!-- Proof Section -->
                                                    @if($purchase->proof_image)
                                                        <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-100">
                                                            <span class="block text-xs font-medium text-emerald-600 uppercase tracking-wide mb-1">Proof of Receipt</span>
                                                            <a href="{{ Storage::url($purchase->proof_image) }}" target="_blank" class="text-emerald-700 hover:underline text-sm flex items-center gap-1">
                                                                <x-heroicon-o-paper-clip class="w-4 h-4" />
                                                                View Uploaded Image
                                                            </a>
                                                        </div>
                                                    @else
                                                        <div class="space-y-1.5">
                                                            <label for="proof_image" class="block text-sm font-medium text-gray-700">Upload Proof of Receipt <span class="text-red-500">*</span></label>
                                                            <input
                                                                id="proof_image"
                                                                type="file"
                                                                name="proof_image"
                                                                accept="image/*"
                                                                required
                                                                class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all cursor-pointer"
                                                            />
                                                            <p class="text-xs text-gray-400">Image (JPG, PNG) max 2MB.</p>
                                                            <x-input-error :messages="$errors->get('proof_image')" class="mt-1" />
                                                        </div>
                                                    @endif

                                                    @if($purchase->invoice_number && $purchase->proof_image)
                                                        <p class="text-xs text-emerald-600 font-medium flex items-center gap-1">
                                                            <x-heroicon-o-check-circle class="w-4 h-4" />
                                                            Data complete. Ready to receive.
                                                        </p>
                                                    @endif
                                                </div>

                                                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                                                    <button type="button" @click="open = false" x-bind:disabled="submitting"
                                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-all focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50">
                                                        <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Batal
                                                    </button>
                                                    <button type="submit" x-bind:disabled="submitting"
                                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 shadow-sm shadow-emerald-200 active:scale-[0.98] transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <svg x-show="submitting" class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <svg x-show="!submitting" class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                        {{ __('Confirm Receipt') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @elseif($purchase->status === \App\Enums\PurchaseStatus::RECEIVED)

                    {{-- Pay Action --}}
                    <x-primary-button
                        type="button"
                        class="!bg-emerald-600 hover:!bg-emerald-700 focus:!ring-emerald-500"
                        @click="confirmAction('{{ route('purchases.mark-paid', $purchase) }}', 'PATCH', 'Mark as Paid', 'Are you sure you want to mark this purchase as paid? This assumes the full amount has been paid.', 'Mark as Paid', '!bg-emerald-600 hover:!bg-emerald-700 focus:!ring-emerald-500')"
                    >
                        <x-heroicon-o-currency-dollar class="w-5 h-5 mr-1" />
                        {{ __('Mark as Paid') }}
                    </x-primary-button>

                @elseif($purchase->status === \App\Enums\PurchaseStatus::CANCELLED)

                    {{-- Restore Action --}}
                    <x-secondary-button
                        type="button"
                        @click="confirmAction('{{ route('purchases.restore-draft', $purchase) }}', 'PATCH', 'Restore to Draft', 'Restore this purchase to Draft status? You can edit it again.', 'Restore to Draft', '!bg-gray-800 hover:!bg-gray-700 text-white')"
                    >
                        {{ __('Restore to Draft') }}
                    </x-secondary-button>

                @endif

                <!-- Shared Confirmation Modal (Premium) -->
                <div
                    x-show="$dispatch !== undefined"
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
                                <!-- Dynamic accent bar -->
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

                                        <form id="confirmation-form" method="POST" x-ref="confirmFormPurchase" @submit.prevent>
                                            @csrf
                                            <input type="hidden" id="confirmation-method" name="_method" value="">
                                            <button
                                                type="button"
                                                x-bind:disabled="submitting"
                                                @click="submitting = true; document.getElementById('confirmation-form').submit()"
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
