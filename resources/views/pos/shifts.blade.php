<x-app-layout title="Shift Kasir">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Manajemen Shift Kasir
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Buka dan tutup shift kasir, pantau saldo laci kas, serta tinjau riwayat selisih kas.
                </p>
            </div>
            @if (\Illuminate\Support\Facades\Route::has('sales.create'))
                <a href="{{ route('sales.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-[#2563EB] hover:bg-[#1D4ED8] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Kembali ke Kasir POS</span>
                </a>
            @endif
        </div>
    </x-slot>

    @php
        $openAction = \Illuminate\Support\Facades\Route::has('pos.shifts.open')
            ? route('pos.shifts.open')
            : url('/pos/shifts/open');

        $closeAction = $activeShift
            ? (\Illuminate\Support\Facades\Route::has('pos.shifts.close')
                ? route('pos.shifts.close', $activeShift)
                : url('/pos/shifts/' . $activeShift->id . '/close'))
            : '#';
    @endphp

    <div class="space-y-6 bg-[#F8FAFC] min-h-full">
        @if (session('success'))
            <div class="rounded-xl border border-[#10B981]/30 bg-[#10B981]/10 px-4 py-3 text-sm font-medium text-[#0F766E] flex items-center gap-2.5">
                <svg class="w-5 h-5 text-[#10B981] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-[#DC2626]/30 bg-[#DC2626]/10 px-4 py-3 text-sm font-medium text-[#DC2626] flex items-center gap-2.5">
                <svg class="w-5 h-5 text-[#DC2626] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-[#DC2626]/30 bg-[#DC2626]/10 px-4 py-3 text-sm text-[#DC2626]">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Active Shift Status & Form Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Summary / Status Panel -->
            <div class="bg-white rounded-xl border border-[#DCE3EA] shadow-sm p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#142B4A]">Status Shift Anda</span>
                        @if ($activeShift)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#10B981]/10 text-[#0F766E] border border-[#10B981]/30">
                                <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                                Shift Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#D97706]/10 text-[#D97706] border border-[#D97706]/30">
                                <span class="w-2 h-2 rounded-full bg-[#D97706]"></span>
                                Belum Ada Shift
                            </span>
                        @endif
                    </div>

                    @if ($activeShift)
                        <div class="space-y-3 text-sm">
                            <div class="p-3 rounded-lg bg-[#F8FAFC] border border-[#DCE3EA]">
                                <div class="text-xs text-slate-500">Register</div>
                                <div class="font-bold text-[#142B4A]">{{ $activeShift->register->name ?? '-' }} ({{ $activeShift->register->code ?? '-' }})</div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-lg bg-[#F8FAFC] border border-[#DCE3EA]">
                                    <div class="text-xs text-slate-500">Saldo Awal</div>
                                    <div class="font-bold text-[#142B4A] mt-0.5">{{ format_money($activeShift->opening_balance) }}</div>
                                </div>
                                <div class="p-3 rounded-lg bg-[#2563EB]/5 border border-[#2563EB]/20">
                                    <div class="text-xs text-[#2563EB] font-semibold">Ekspektasi Kas</div>
                                    <div class="font-bold text-[#142B4A] mt-0.5">{{ format_money($expectedCash ?? $activeShift->opening_balance) }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-slate-500">
                                Dibuka pada: <span class="font-semibold text-[#142B4A]">{{ $activeShift->opened_at?->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="py-6 text-center text-slate-500 text-sm">
                            <p>Anda belum membuka shift kasir saat ini.</p>
                            <p class="text-xs text-slate-400 mt-1">Buka shift dengan memasukkan modal awal laci kas pada formulir di samping.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Open or Close Shift Form -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-[#DCE3EA] shadow-sm p-5">
                @if ($activeShift)
                    <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3 mb-4">
                        <div>
                            <h3 class="text-base font-bold text-[#142B4A]">Tutup Shift Kasir</h3>
                            <p class="text-xs text-slate-500">Hitung total uang tunai fisik di laci untuk rekonsiliasi akhir shift.</p>
                        </div>
                        <span class="text-xs font-mono font-semibold text-[#0F766E] bg-[#0F766E]/10 px-2.5 py-1 rounded-lg">
                            #SHIFT-{{ $activeShift->id }}
                        </span>
                    </div>

                    <form method="POST" action="{{ $closeAction }}" class="space-y-4"
                          x-data="{
                              expected: {{ (float) ($expectedCash ?? $activeShift->opening_balance) }},
                              actual: '{{ old('actual_closing_balance', (float) ($expectedCash ?? $activeShift->opening_balance)) }}',
                              get variance() {
                                  const a = parseFloat(this.actual);
                                  return isNaN(a) ? 0 : (a - this.expected);
                              }
                          }">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#142B4A] mb-1.5">Ekspektasi Saldo Kas Sistem</label>
                                <div class="w-full px-3.5 py-2.5 rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] text-sm font-bold text-[#142B4A]">
                                    {{ format_money($expectedCash ?? $activeShift->opening_balance) }}
                                </div>
                            </div>

                            <div>
                                <label for="actual_closing_balance" class="block text-xs font-semibold text-[#142B4A] mb-1.5">
                                    Saldo Kas Fisik Aktual <span class="text-[#DC2626]">*</span>
                                </label>
                                <input id="actual_closing_balance" name="actual_closing_balance" type="number" step="0.01" min="0" required
                                       x-model="actual"
                                       class="w-full rounded-lg border border-[#DCE3EA] px-3.5 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                            </div>
                        </div>

                        <!-- Live Variance Preview -->
                        <div class="p-3.5 rounded-lg border flex items-center justify-between"
                             :class="variance === 0 ? 'bg-[#10B981]/10 border-[#10B981]/30 text-[#0F766E]' : (variance > 0 ? 'bg-[#2563EB]/10 border-[#2563EB]/30 text-[#2563EB]' : 'bg-[#DC2626]/10 border-[#DC2626]/30 text-[#DC2626]')">
                            <span class="text-xs font-semibold uppercase tracking-wider">Perkiraan Selisih (Variance)</span>
                            <span class="text-sm font-bold" x-text="window.formatMoney ? window.formatMoney(variance) : variance.toFixed(2)"></span>
                        </div>

                        <div>
                            <label for="close_notes" class="block text-xs font-semibold text-[#142B4A] mb-1.5">Catatan Penutupan Shift (Opsional)</label>
                            <textarea id="close_notes" name="notes" rows="2" maxlength="500"
                                      placeholder="Contoh: Selisih karena uang kembalian receh..."
                                      class="w-full rounded-lg border border-[#DCE3EA] px-3.5 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]">{{ old('notes') }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#DC2626] hover:bg-[#B91C1C] transition-colors shadow-sm">
                                Tutup Shift Sekarang
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-3 mb-4">
                        <div>
                            <h3 class="text-base font-bold text-[#142B4A]">Buka Shift Kasir Baru</h3>
                            <p class="text-xs text-slate-500">Pilih mesin register dan masukkan modal awal kasir sebelum bertransaksi.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ $openAction }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="cash_register_id" class="block text-xs font-semibold text-[#142B4A] mb-1.5">Mesin Register</label>
                                <select id="cash_register_id" name="cash_register_id"
                                        class="w-full rounded-lg border border-[#DCE3EA] px-3.5 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]">
                                    @foreach ($registers as $register)
                                        <option value="{{ $register->id }}" @selected(old('cash_register_id') == $register->id)>
                                            {{ $register->name }} ({{ $register->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="opening_balance" class="block text-xs font-semibold text-[#142B4A] mb-1.5">
                                    Saldo Awal Kas (Modal Laci) <span class="text-[#DC2626]">*</span>
                                </label>
                                <input id="opening_balance" name="opening_balance" type="number" step="0.01" min="0" required
                                       value="{{ old('opening_balance', 0) }}"
                                       class="w-full rounded-lg border border-[#DCE3EA] px-3.5 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                            </div>
                        </div>

                        <div>
                            <label for="open_notes" class="block text-xs font-semibold text-[#142B4A] mb-1.5">Catatan Pembukaan Shift (Opsional)</label>
                            <textarea id="open_notes" name="notes" rows="2" maxlength="500"
                                      placeholder="Catatan kondisi laci kas saat buka shift..."
                                      class="w-full rounded-lg border border-[#DCE3EA] px-3.5 py-2 text-sm text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]">{{ old('notes') }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#2563EB] hover:bg-[#1D4ED8] transition-colors shadow-sm">
                                Buka Shift Kasir
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <!-- Shift History Table -->
        <div class="bg-white rounded-xl border border-[#DCE3EA] shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-[#DCE3EA] flex items-center justify-between bg-[#F8FAFC]">
                <div>
                    <h3 class="text-sm font-bold text-[#142B4A]">Riwayat Shift Kasir</h3>
                    <p class="text-xs text-slate-500">Daftar seluruh sesi shift kasir beserta rekonsiliasi saldo.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#F8FAFC]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-[#142B4A]">Kasir</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-[#142B4A]">Register</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-[#142B4A]">Waktu</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-[#142B4A]">Saldo Awal</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-[#142B4A]">Ekspektasi</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-[#142B4A]">Aktual</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-[#142B4A]">Selisih</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-[#142B4A]">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($shifts as $item)
                            @php
                                $variance = (float) $item->variance;
                            @endphp
                            <tr class="hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3 font-medium text-[#142B4A]">
                                    {{ $item->user->name ?? 'User #' . $item->user_id }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $item->register->name ?? '-' }}
                                    <span class="text-xs text-slate-400 font-mono">({{ $item->register->code ?? '-' }})</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div>Buka: {{ $item->opened_at?->format('d/m/Y H:i') }}</div>
                                    <div class="text-slate-400">
                                        Tutup: {{ $item->closed_at ? $item->closed_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-slate-700">
                                    {{ format_money($item->opening_balance) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-[#142B4A] font-semibold">
                                    {{ format_money($item->expected_closing_balance) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-slate-700">
                                    {{ $item->actual_closing_balance !== null ? format_money($item->actual_closing_balance) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($item->status === 'closed')
                                        @if ($variance == 0.0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#10B981]/10 text-[#0F766E]">
                                                {{ format_money(0) }}
                                            </span>
                                        @elseif ($variance > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#2563EB]/10 text-[#2563EB]">
                                                +{{ format_money($variance) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#DC2626]/10 text-[#DC2626]">
                                                {{ format_money($variance) }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($item->status === 'open')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#10B981]/10 text-[#0F766E] border border-[#10B981]/30">
                                            Open
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-[#142B4A] border border-[#DCE3EA]">
                                            Closed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">
                                    Belum ada riwayat shift kasir.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($shifts->hasPages())
                <div class="px-5 py-3 border-t border-[#DCE3EA] bg-[#F8FAFC]">
                    {{ $shifts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
