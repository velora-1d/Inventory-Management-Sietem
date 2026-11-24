@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('inventory.index') ? route('inventory.index') : url('/inventory');
    $movementsUrl = \Illuminate\Support\Facades\Route::has('inventory.movements') ? route('inventory.movements') : url('/inventory/movements');
    $storeOpnameUrl = \Illuminate\Support\Facades\Route::has('inventory.opnames.store') ? route('inventory.opnames.store') : url('/inventory/opnames');
@endphp

<x-app-layout title="Stock Opnames">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Stock Opname &amp; Physical Reconciliation
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Record physical warehouse counts and apply approved variance adjustments to the stock ledger.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $indexUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    &larr; Stock Balances
                </a>
                <a href="{{ $movementsUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#2563EB] bg-[#2563EB] text-white hover:bg-[#1D4ED8] transition-colors">
                    Movement Ledger
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        rows: [{ product_id: '', counted_qty: 0, notes: '' }],
        addRow() { this.rows.push({ product_id: '', counted_qty: 0, notes: '' }); },
        removeRow(index) { if (this.rows.length > 1) this.rows.splice(index, 1); }
    }">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-[#0F766E] font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#DC2626] font-medium">
                {{ session('error') }}
            </div>
        @endif

        {{-- Create New Stock Opname --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-[#142B4A] text-white flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold">New Stock Opname Sheet</h3>
                    <p class="text-xs text-sky-200">Compare physical counted quantity against system on-hand stock.</p>
                </div>
            </div>

            <form method="POST" action="{{ $storeOpnameUrl }}" class="p-5 space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="opname_warehouse_id" class="block text-xs font-semibold text-[#142B4A] mb-1">Warehouse</label>
                        <select id="opname_warehouse_id"
                                name="warehouse_id"
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($wh->id === $defaultWarehouse->id)>
                                    {{ $wh->name }} ({{ $wh->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="opname_reason" class="block text-xs font-semibold text-[#142B4A] mb-1">Reason / Audit Period</label>
                        <input id="opname_reason"
                               type="text"
                               name="reason"
                               value="{{ old('reason') }}"
                               placeholder="e.g. Monthly cycle count"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#142B4A]">Counted Items</span>
                        <button type="button"
                                @click="addRow()"
                                class="px-3 py-1 rounded-lg border border-[#2563EB] text-xs font-semibold text-[#2563EB] hover:bg-blue-50 transition-colors">
                            + Add Item Row
                        </button>
                    </div>

                    <template x-for="(row, index) in rows" :key="index">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-[#F8FAFC] border border-[#DCE3EA] rounded-lg p-2.5">
                            <div class="md:col-span-6">
                                <select :name="`items[${index}][product_id]`"
                                        x-model="row.product_id"
                                        required
                                        class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                                    <option value="">-- Select Product --</option>
                                    @foreach ($products as $prod)
                                        @php
                                            $sysQty = (int) ($balancesByProduct[$prod->id] ?? $prod->quantity);
                                        @endphp
                                        <option value="{{ $prod->id }}">{{ $prod->sku }} — {{ $prod->name }} (System: {{ $sysQty }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <input type="number"
                                       min="0"
                                       :name="`items[${index}][counted_qty]`"
                                       x-model="row.counted_qty"
                                       placeholder="Counted"
                                       required
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                            </div>
                            <div class="md:col-span-3">
                                <input type="text"
                                       :name="`items[${index}][notes]`"
                                       x-model="row.notes"
                                       placeholder="Notes (optional)"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                            </div>
                            <div class="md:col-span-1 text-right">
                                <button type="button"
                                        @click="removeRow(index)"
                                        x-show="rows.length > 1"
                                        class="px-2.5 py-2 rounded-lg text-xs font-semibold text-[#DC2626] hover:bg-red-50">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-[#DC2626]">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex justify-end pt-2 border-t border-[#DCE3EA]">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-[#0F766E] hover:bg-[#0D655E] text-white text-sm font-semibold transition-colors">
                        Create Opname Draft
                    </button>
                </div>
            </form>
        </div>

        {{-- Opnames List --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Opname #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Items &amp; Variances</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Created / Approved</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($opnames as $opname)
                            @php
                                $approveUrl = \Illuminate\Support\Facades\Route::has('inventory.opnames.approve')
                                    ? route('inventory.opnames.approve', $opname)
                                    : url('/inventory/opnames/' . $opname->id . '/approve');
                                $statusBadge = match ($opname->status) {
                                    'approved' => 'bg-emerald-50 text-[#0F766E] border-emerald-200',
                                    'rejected' => 'bg-red-50 text-[#DC2626] border-red-200',
                                    'submitted' => 'bg-blue-50 text-[#2563EB] border-blue-200',
                                    default => 'bg-amber-50 text-[#D97706] border-amber-200',
                                };
                            @endphp
                            <tr class="hover:bg-[#F8FAFC] transition-colors align-top">
                                <td class="px-4 py-3">
                                    <div class="font-mono font-bold text-[#142B4A]">{{ $opname->opname_number }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $opname->reason ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700 text-xs font-medium">
                                    {{ $opname->warehouse->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <ul class="space-y-1 text-xs">
                                        @foreach ($opname->items as $item)
                                            <li class="flex items-center justify-between gap-4 border-b border-slate-100 pb-1 last:border-0 last:pb-0">
                                                <span class="text-slate-700 font-medium">
                                                    {{ $item->product->name ?? 'Product #' . $item->product_id }}
                                                </span>
                                                <span class="font-mono whitespace-nowrap">
                                                    Sys {{ $item->system_qty }} / Cnt {{ $item->counted_qty }}
                                                    (<span class="font-bold {{ $item->difference_qty > 0 ? 'text-[#10B981]' : ($item->difference_qty < 0 ? 'text-[#DC2626]' : 'text-slate-500') }}">
                                                        {{ $item->difference_qty > 0 ? '+' . $item->difference_qty : $item->difference_qty }}
                                                    </span>)
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border uppercase {{ $statusBadge }}">
                                        {{ $opname->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div>By: {{ $opname->creator->name ?? 'System' }}</div>
                                    @if ($opname->approved_at)
                                        <div class="text-[#0F766E] mt-0.5">
                                            Approved by {{ $opname->approver->name ?? 'User' }} ({{ $opname->approved_at->format('Y-m-d H:i') }})
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if (in_array($opname->status, ['draft', 'submitted'], true))
                                        <form method="POST" action="{{ $approveUrl }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg bg-[#10B981] hover:bg-[#059669] text-white text-xs font-semibold transition-colors">
                                                Approve &amp; Apply
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Finalized</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    No stock opnames recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($opnames->hasPages())
                <div class="px-4 py-3 border-t border-[#DCE3EA] bg-[#F8FAFC]">
                    {{ $opnames->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
