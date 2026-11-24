@php
    $refundIndexUrl = \Illuminate\Support\Facades\Route::has('finance.refunds.index')
        ? route('finance.refunds.index')
        : url('/finance/refunds');
    $refundStoreUrl = \Illuminate\Support\Facades\Route::has('finance.refunds.store')
        ? route('finance.refunds.store')
        : url('/finance/refunds');
    $hasApproveRoute = \Illuminate\Support\Facades\Route::has('finance.refunds.approve');
    $hasProcessRoute = \Illuminate\Support\Facades\Route::has('finance.refunds.process');
    $hasRejectRoute = \Illuminate\Support\Facades\Route::has('finance.refunds.reject');
@endphp

<x-app-layout title="Refund Management">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Refund Management
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Kelola pengajuan refund order pelanggan dan transaksi POS, persetujuan, serta pencatatan pengeluaran kas.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        createOpen: {{ $errors->any() ? 'true' : 'false' }},
        rejectOpen: false,
        rejectAction: '',
        rejectNumber: '',
        openReject(number, url) {
            this.rejectNumber = number;
            this.rejectAction = url;
            this.rejectOpen = true;
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
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Pengajuan</p>
                <p class="mt-1 text-2xl font-bold text-[#142B4A]">{{ number_format($summary['total_refunds']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Menunggu Persetujuan</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ number_format($summary['requested_count']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Siap Diproses</p>
                <p class="mt-1 text-2xl font-bold text-[#2563EB]">{{ number_format($summary['approved_count']) }}</p>
            </div>
            <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Refund Selesai</p>
                <p class="mt-1 text-2xl font-bold text-[#10B981]">{{ format_money($summary['processed_total']) }}</p>
            </div>
        </div>

        {{-- Filter & New Refund Trigger --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white p-4 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <form method="GET" action="{{ $refundIndexUrl }}" class="flex-1 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Cari nomor refund, alasan, atau referensi..."
                           class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />

                    <select name="status" class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                        <option value="">Semua Status</option>
                        @foreach(['requested', 'approved', 'processed', 'rejected'] as $st)
                            <option value="{{ $st }}" @selected($status === $st)>{{ strtoupper($st) }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 rounded-lg bg-[#142B4A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1E3A5F]">
                            Filter
                        </button>
                        @if($search !== '' || $status !== '')
                            <a href="{{ $refundIndexUrl }}"
                               class="rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-[#F8FAFC]">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                <button type="button"
                        @click="createOpen = !createOpen"
                        class="inline-flex items-center justify-center rounded-lg bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1D4ED8] transition-colors">
                    + Ajukan Refund
                </button>
            </div>

            {{-- Create Refund Form --}}
            <div x-show="createOpen" x-cloak class="mt-4 border-t border-[#DCE3EA] pt-4">
                <form method="POST" action="{{ $refundStoreUrl }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Order Pelanggan (Opsional)</label>
                        <select name="order_id" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                            <option value="">-- Tidak terkait Order --</option>
                            @foreach($recentOrders as $order)
                                <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                                    {{ $order->order_number }} ({{ format_money($order->total) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Transaksi POS (Opsional)</label>
                        <select name="sale_id" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                            <option value="">-- Tidak terkait Sale --</option>
                            @foreach($recentSales as $sale)
                                <option value="{{ $sale->id }}" @selected((string) old('sale_id') === (string) $sale->id)>
                                    {{ $sale->invoice_number }} ({{ format_money($sale->total) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Nominal Refund</label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               name="amount"
                               value="{{ old('amount') }}"
                               required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Metode Refund</label>
                        <select name="method" required class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]">
                            <option value="transfer" @selected(old('method') === 'transfer')>Bank Transfer</option>
                            <option value="cash" @selected(old('method') === 'cash')>Cash</option>
                            <option value="ewallet" @selected(old('method') === 'ewallet')>E-Wallet</option>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Alasan Refund</label>
                        <input type="text"
                               name="reason"
                               value="{{ old('reason') }}"
                               required
                               placeholder="Jelaskan alasan pengajuan refund..."
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]" />
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full rounded-lg bg-[#0F766E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0D655E]">
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Refunds Table --}}
        <div class="rounded-xl border border-[#DCE3EA] bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[#DCE3EA] bg-[#F8FAFC] text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="px-5 py-3">Refund #</th>
                            <th class="px-4 py-3">Sumber</th>
                            <th class="px-4 py-3">Alasan &amp; Pemohon</th>
                            <th class="px-4 py-3">Metode</th>
                            <th class="px-4 py-3 text-right">Nominal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA]">
                        @forelse($refunds as $refund)
                            @php
                                $approveUrl = $hasApproveRoute
                                    ? route('finance.refunds.approve', $refund)
                                    : url('/finance/refunds/' . $refund->id . '/approve');
                                $processUrl = $hasProcessRoute
                                    ? route('finance.refunds.process', $refund)
                                    : url('/finance/refunds/' . $refund->id . '/process');
                                $rejectUrl = $hasRejectRoute
                                    ? route('finance.refunds.reject', $refund)
                                    : url('/finance/refunds/' . $refund->id . '/reject');
                            @endphp
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-[#142B4A]">{{ $refund->refund_number }}</span>
                                    <p class="text-[11px] text-slate-400">{{ $refund->created_at?->format('d M Y H:i') }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    @if($refund->order)
                                        <span class="inline-flex rounded bg-blue-50 px-2 py-0.5 font-semibold text-[#2563EB]">
                                            Order: {{ $refund->order->order_number }}
                                        </span>
                                    @elseif($refund->sale)
                                        <span class="inline-flex rounded bg-teal-50 px-2 py-0.5 font-semibold text-[#0F766E]">
                                            POS: {{ $refund->sale->invoice_number }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Umum</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="text-[#142B4A] font-medium">{{ $refund->reason }}</p>
                                    <p class="text-xs text-slate-500">
                                        Oleh: {{ $refund->requester?->name ?? '-' }}
                                        @if($refund->reference)
                                            &middot; Ref: {{ $refund->reference }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3.5 uppercase text-xs font-semibold text-slate-600">
                                    {{ $refund->method }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-[#142B4A]">
                                    {{ format_money($refund->amount) }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $refund->statusBadgeClasses() }}">
                                        {{ strtoupper($refund->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-1.5">
                                        @if($refund->status === 'requested')
                                            <form method="POST" action="{{ $approveUrl }}">
                                                @csrf
                                                <button type="submit"
                                                        class="rounded-lg bg-[#2563EB] px-2.5 py-1 text-xs font-semibold text-white hover:bg-[#1D4ED8]">
                                                    Approve
                                                </button>
                                            </form>
                                        @endif

                                        @if(in_array($refund->status, ['requested', 'approved'], true))
                                            <form method="POST" action="{{ $processUrl }}">
                                                @csrf
                                                <button type="submit"
                                                        class="rounded-lg bg-[#10B981] px-2.5 py-1 text-xs font-semibold text-white hover:bg-[#059669]">
                                                    Process
                                                </button>
                                            </form>

                                            <button type="button"
                                                    @click="openReject(@js($refund->refund_number), @js($rejectUrl))"
                                                    class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-[#DC2626] hover:bg-red-100">
                                                Reject
                                            </button>
                                        @endif

                                        @if(in_array($refund->status, ['processed', 'rejected'], true))
                                            <span class="text-xs text-slate-400">
                                                {{ $refund->processor?->name ?? 'Selesai' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Belum ada data pengajuan refund.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[#DCE3EA] px-5 py-3">
                {{ $refunds->links() }}
            </div>
        </div>

        {{-- Reject Modal --}}
        <div x-show="rejectOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="rejectOpen = false"
                 class="w-full max-w-md rounded-2xl border border-[#DCE3EA] bg-white p-6 shadow-xl">
                <h3 class="text-base font-bold text-[#142B4A]">Tolak Refund <span class="text-[#DC2626]" x-text="rejectNumber"></span></h3>
                <form method="POST" :action="rejectAction" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Alasan Penolakan</label>
                        <textarea name="reason"
                                  rows="3"
                                  required
                                  placeholder="Tuliskan alasan penolakan refund..."
                                  class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-[#142B4A]"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                                @click="rejectOpen = false"
                                class="rounded-lg border border-[#DCE3EA] bg-white px-4 py-2 text-xs font-semibold text-slate-600">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-[#DC2626] px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">
                            Tolak Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
