@php
    $indexUrl = \Illuminate\Support\Facades\Route::has('procurement.index') ? route('procurement.index') : url('/procurement');
    $requestsUrl = \Illuminate\Support\Facades\Route::has('procurement.requests') ? route('procurement.requests') : url('/procurement/requests');
    $storeUrl = \Illuminate\Support\Facades\Route::has('procurement.requests.store') ? route('procurement.requests.store') : url('/procurement/requests');
    $ordersUrl = \Illuminate\Support\Facades\Route::has('procurement.orders') ? route('procurement.orders') : url('/procurement/orders');
@endphp

<x-app-layout title="Purchase Requests">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Purchase Requests (PR)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Create internal stock requisition requests and track approval decisions.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $indexUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC]">
                    Overview
                </a>
                <a href="{{ $ordersUrl }}" class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#2563EB] hover:bg-[#F8FAFC]">
                    Purchase Orders
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        createOpen: {{ $errors->any() ? 'true' : 'false' }},
        rejectOpen: false,
        rejectAction: '',
        rejectPrNumber: '',
        items: [{ product_id: '', quantity: 1, estimated_unit_price: 0, notes: '' }],
        addItem() { this.items.push({ product_id: '', quantity: 1, estimated_unit_price: 0, notes: '' }); },
        removeItem(idx) { if (this.items.length > 1) this.items.splice(idx, 1); },
        openReject(url, prNumber) {
            this.rejectAction = url;
            this.rejectPrNumber = prNumber;
            this.rejectOpen = true;
        }
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

        {{-- Filter & New Trigger --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
            <form method="GET" action="{{ $requestsUrl }}" class="flex flex-col md:flex-row md:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Search PR Number / Notes</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="PR-2026..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">All Statuses</option>
                        <option value="pending_approval" @selected($status === 'pending_approval')>Pending Approval</option>
                        <option value="approved" @selected($status === 'approved')>Approved</option>
                        <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">
                        Filter
                    </button>
                    <a href="{{ $requestsUrl }}" class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-slate-600 text-sm font-medium">
                        Reset
                    </a>
                    <button type="button" @click="createOpen = !createOpen"
                            class="px-4 py-2 rounded-lg bg-[#142B4A] hover:bg-[#0F223B] text-white text-sm font-semibold">
                        + New Request
                    </button>
                </div>
            </form>
        </div>

        {{-- Create PR Form --}}
        <div x-show="createOpen" x-cloak class="bg-white border border-[#DCE3EA] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-[#142B4A]">New Purchase Request</h3>
                <button type="button" @click="createOpen = false" class="text-xs font-semibold text-slate-500 hover:text-[#DC2626]">Close</button>
            </div>
            <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-[#142B4A] mb-1">Request Notes</label>
                    <textarea name="notes" rows="2" placeholder="Urgent stock replenishment notes..."
                              class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#142B4A]">Requested Line Items</span>
                        <button type="button" @click="addItem()" class="text-xs font-semibold text-[#2563EB] hover:underline">+ Add Line</button>
                    </div>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center bg-[#F8FAFC] p-3 rounded-lg border border-[#DCE3EA]">
                            <div class="md:col-span-5">
                                <select :name="`items[${index}][product_id]`" x-model="item.product_id" required
                                        class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm">
                                    <option value="">Select product...</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <input type="number" min="1" :name="`items[${index}][quantity]`" x-model="item.quantity" required placeholder="Qty"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <input type="number" step="0.01" min="0" :name="`items[${index}][estimated_unit_price]`" x-model="item.estimated_unit_price" placeholder="Est. Price"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <input type="text" :name="`items[${index}][notes]`" x-model="item.notes" placeholder="Item note"
                                       class="w-full rounded-lg border border-[#DCE3EA] bg-white px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-1 text-right">
                                <button type="button" @click="removeItem(index)" class="text-xs font-semibold text-[#DC2626] hover:underline">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="createOpen = false" class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">Submit Purchase Request</button>
                </div>
            </form>
        </div>

        {{-- Requests Table --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">PR Number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Requester</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Items</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Notes / Decision</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($requests as $pr)
                            @php
                                $approveUrl = \Illuminate\Support\Facades\Route::has('procurement.requests.approve')
                                    ? route('procurement.requests.approve', $pr)
                                    : url("/procurement/requests/{$pr->id}/approve");
                                $rejectUrl = \Illuminate\Support\Facades\Route::has('procurement.requests.reject')
                                    ? route('procurement.requests.reject', $pr)
                                    : url("/procurement/requests/{$pr->id}/reject");
                            @endphp
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3 font-mono font-bold text-[#142B4A]">
                                    {{ $pr->pr_number }}
                                    <div class="text-[11px] font-sans font-normal text-slate-500">{{ $pr->created_at?->format('Y-m-d H:i') }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $pr->requester->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <ul class="space-y-0.5 text-xs text-slate-700">
                                        @foreach ($pr->items as $item)
                                            <li>{{ $item->product->name ?? 'Product' }} &times; <span class="font-semibold">{{ $item->quantity }}</span></li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        @if ($pr->status === 'approved') bg-emerald-50 text-[#10B981] border border-emerald-200
                                        @elseif ($pr->status === 'rejected') bg-red-50 text-[#DC2626] border border-red-200
                                        @else bg-amber-50 text-[#D97706] border border-amber-200 @endif">
                                        {{ str_replace('_', ' ', ucfirst($pr->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div>{{ $pr->notes ?: '-' }}</div>
                                    @if ($pr->rejection_reason)
                                        <div class="text-[#DC2626] mt-1 font-medium">Rejected: {{ $pr->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($pr->status === 'pending_approval')
                                        <div class="inline-flex items-center gap-2">
                                            <form method="POST" action="{{ $approveUrl }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#10B981] hover:bg-[#0F766E] text-white text-xs font-semibold">
                                                    Approve
                                                </button>
                                            </form>
                                            <button type="button" @click="openReject('{{ $rejectUrl }}', '{{ $pr->pr_number }}')"
                                                    class="px-3 py-1.5 rounded-lg bg-[#DC2626] hover:bg-red-700 text-white text-xs font-semibold">
                                                Reject
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">By {{ $pr->approver->name ?? '-' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No purchase requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-[#DCE3EA]">
                {{ $requests->links() }}
            </div>
        </div>

        {{-- Reject Modal --}}
        <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="bg-white border border-[#DCE3EA] rounded-xl max-w-md w-full p-5 shadow-xl">
                <h3 class="text-base font-bold text-[#142B4A]">Reject Purchase Request <span class="font-mono" x-text="rejectPrNumber"></span></h3>
                <form method="POST" :action="rejectAction" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Rejection Reason</label>
                        <textarea name="reason" rows="3" required placeholder="State why this PR is rejected..."
                                  class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="rejectOpen = false" class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-[#DC2626] text-white text-xs font-semibold">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
