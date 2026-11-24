@php
    $apIndexUrl = \Illuminate\Support\Facades\Route::has('finance.ap.index')
        ? route('finance.ap.index')
        : url('/finance/ap');
    $hasPayRoute = \Illuminate\Support\Facades\Route::has('finance.ap.pay');
@endphp

<x-app-layout title="Accounts Payable">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Accounts Payable (Hutang Supplier)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pantau tagihan supplier, sisa hutang jatuh tempo, dan catat pembayaran ke buku kas keuangan.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        payModalOpen: false,
        activeApId: null,
        activeInvoice: '',
        activeBalance: 0,
        payAction: '',
        openPayModal(id, invoice, balance, actionUrl) {
            this.activeApId = id;
            this.activeInvoice = invoice;
            this.activeBalance = balance;
            this.payAction = actionUrl;
            this.payModalOpen = true;
        }
    }">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-[#0F766E]">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-[#DC2626]">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#DC2626]">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Hutang Tercatat</p>
                <p class="mt-1 text-2xl font-bold text-[#142B4A]">{{ format_money($summary['total_payable']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Terbayar</p>
                <p class="mt-1 text-2xl font-bold text-[#10B981]">{{ format_money($summary['total_paid']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sisa Jatuh Tempo (Due)</p>
                <p class="mt-1 text-2xl font-bold text-[#DC2626]">{{ format_money($summary['total_due']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Invoice Belum Lunas</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ number_format($summary['open_count']) }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
            <form method="GET" action="{{ $apIndexUrl }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nomor invoice atau nama supplier..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                </div>

                <div>
                    <select name="status" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">Semua Status</option>
                        <option value="unpaid" @selected($status === 'unpaid')>UNPAID</option>
                        <option value="partial" @selected($status === 'partial')>PARTIAL</option>
                        <option value="paid" @selected($status === 'paid')>PAID</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 rounded-lg bg-[#142B4A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1E3A5F] transition-colors">
                        Filter
                    </button>
                    @if($search !== '' || $status !== '')
                        <a href="{{ $apIndexUrl }}"
                           class="rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-[#F8FAFC]">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- AP Table --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[#DCE3EA] bg-[#F8FAFC] text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Invoice #</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Jatuh Tempo</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Dibayar</th>
                            <th class="px-4 py-3 text-right">Sisa Hutang</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA]">
                        @forelse($payables as $ap)
                            @php
                                $payUrl = $hasPayRoute
                                    ? route('finance.ap.pay', $ap)
                                    : url('/finance/ap/' . $ap->id . '/pay');
                            @endphp
                            <tr class="hover:bg-[#F8FAFC] align-top">
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-[#142B4A]">{{ $ap->invoice_number }}</span>
                                    @if($ap->payments->isNotEmpty())
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            {{ $ap->payments->count() }} pembayaran tercatat
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-[#142B4A]">{{ $ap->supplier?->name ?? '-' }}</p>
                                    @if($ap->notes)
                                        <p class="text-xs text-slate-500 truncate max-w-xs">{{ $ap->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">
                                    {{ $ap->due_date ? $ap->due_date->format('d M Y') : '-' }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-medium text-[#142B4A]">
                                    {{ format_money($ap->total_amount) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-medium text-[#10B981]">
                                    {{ format_money($ap->paid_amount) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-[#DC2626]">
                                    {{ format_money($ap->balance_due) }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $ap->statusBadgeClasses() }}">
                                        {{ strtoupper($ap->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if((float) $ap->balance_due > 0 && $ap->status !== 'paid')
                                        <button type="button"
                                                @click="openPayModal({{ $ap->id }}, @js($ap->invoice_number), {{ (float) $ap->balance_due }}, @js($payUrl))"
                                                class="inline-flex items-center rounded-lg bg-[#2563EB] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1D4ED8] transition-colors">
                                            Bayar Hutang
                                        </button>
                                    @else
                                        <span class="text-xs font-semibold text-[#0F766E]">Lunas</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Belum ada data Accounts Payable.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[#DCE3EA] px-5 py-3">
                {{ $payables->links() }}
            </div>
        </div>

        {{-- Payment Modal --}}
        <div x-show="payModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="payModalOpen = false"
                 class="w-full max-w-md rounded-2xl border border-[#DCE3EA] bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3">
                    <div>
                        <h3 class="text-base font-bold text-[#142B4A]">Catat Pembayaran AP</h3>
                        <p class="text-xs text-slate-500">Invoice: <span class="font-semibold text-[#2563EB]" x-text="activeInvoice"></span></p>
                    </div>
                    <button type="button" @click="payModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <form method="POST" :action="payAction" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Jumlah Pembayaran</label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               name="amount"
                               x-model="activeBalance"
                               required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#142B4A] mb-1">Metode Pembayaran</label>
                            <select name="payment_method" required class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                                <option value="transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="giro">Giro / Cheque</option>
                                <option value="ewallet">E-Wallet</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#142B4A] mb-1">Tanggal Bayar</label>
                            <input type="date"
                                   name="payment_date"
                                   value="{{ now()->toDateString() }}"
                                   required
                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Referensi Transfer / Bukti</label>
                        <input type="text"
                               name="reference"
                               placeholder="No. referensi bank / bukti bayar..."
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Catatan</label>
                        <textarea name="notes"
                                  rows="2"
                                  placeholder="Catatan tambahan..."
                                  class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-[#DCE3EA]">
                        <button type="button"
                                @click="payModalOpen = false"
                                class="rounded-lg border border-[#DCE3EA] bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-[#F8FAFC]">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-[#0F766E] px-4 py-2 text-xs font-semibold text-white hover:bg-[#0D655E]">
                            Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
