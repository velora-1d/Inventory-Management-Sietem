<x-app-layout title="Pengiriman & Tracking">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Manajemen Pengiriman &amp; Resi
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Proses dispatch pesanan siap kirim, catat nomor resi kurir, dan konfirmasi penerimaan paket.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#2563EB]/10 text-[#2563EB] border border-[#2563EB]/20">
                    <span class="w-2 h-2 rounded-full bg-[#2563EB]"></span>
                    Blueprint Shipping Console
                </span>
            </div>
        </div>
    </x-slot>

    @php
        $statusStyles = [
            'confirmed' => ['bg' => 'bg-[#2563EB]/10', 'text' => 'text-[#2563EB]', 'border' => 'border-[#2563EB]/25', 'label' => 'Confirmed'],
            'processing' => ['bg' => 'bg-[#D97706]/10', 'text' => 'text-[#D97706]', 'border' => 'border-[#D97706]/25', 'label' => 'Processing'],
            'ready_to_ship' => ['bg' => 'bg-[#0F766E]/10', 'text' => 'text-[#0F766E]', 'border' => 'border-[#0F766E]/25', 'label' => 'Ready to Ship'],
            'ready' => ['bg' => 'bg-[#0F766E]/10', 'text' => 'text-[#0F766E]', 'border' => 'border-[#0F766E]/25', 'label' => 'Ready'],
            'shipped' => ['bg' => 'bg-[#2563EB]/10', 'text' => 'text-[#2563EB]', 'border' => 'border-[#2563EB]/25', 'label' => 'Shipped'],
            'in_transit' => ['bg' => 'bg-[#D97706]/10', 'text' => 'text-[#D97706]', 'border' => 'border-[#D97706]/25', 'label' => 'In Transit'],
            'delivered' => ['bg' => 'bg-[#10B981]/10', 'text' => 'text-[#0F766E]', 'border' => 'border-[#10B981]/30', 'label' => 'Delivered'],
            'completed' => ['bg' => 'bg-[#10B981]/10', 'text' => 'text-[#0F766E]', 'border' => 'border-[#10B981]/30', 'label' => 'Completed'],
            'failed' => ['bg' => 'bg-[#DC2626]/10', 'text' => 'text-[#DC2626]', 'border' => 'border-[#DC2626]/25', 'label' => 'Failed'],
        ];
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

        <!-- Antrian Pesanan Siap Kirim / Dispatch -->
        <div class="bg-white rounded-xl border border-[#DCE3EA] shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-[#DCE3EA] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-[#F8FAFC]">
                <div>
                    <h3 class="text-base font-bold text-[#142B4A]">Antrian Pesanan &amp; Dispatch</h3>
                    <p class="text-xs text-slate-500">Daftar pesanan terkonfirmasi hingga selesai beserta form input resi pengiriman.</p>
                </div>
                <span class="text-xs font-semibold text-[#142B4A] bg-white border border-[#DCE3EA] px-3 py-1 rounded-lg">
                    Total: {{ $orders->total() }} pesanan
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#F8FAFC] text-xs font-bold uppercase tracking-wider text-[#142B4A]">
                        <tr>
                            <th class="px-5 py-3 text-left">Order</th>
                            <th class="px-5 py-3 text-left">Tipe</th>
                            <th class="px-5 py-3 text-left">Status Order</th>
                            <th class="px-5 py-3 text-left">Resi &amp; Kurir</th>
                            <th class="px-5 py-3 text-left">Tracking Publik</th>
                            <th class="px-5 py-3 text-left">Dispatch Pengiriman</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($orders as $order)
                            @php
                                $orderShipment = $shipmentsByOrderId->get($order->id);
                                $orderBadge = $statusStyles[$order->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'label' => ucfirst((string) $order->status)];
                                $dispatchAction = \Illuminate\Support\Facades\Route::has('shipping.dispatch')
                                    ? route('shipping.dispatch', $order)
                                    : url('/shipping/' . $order->id . '/dispatch');
                                $publicTrackUrl = ! empty($order->public_tracking_token)
                                    ? (\Illuminate\Support\Facades\Route::has('tracking.show')
                                        ? route('tracking.show', $order->public_tracking_token)
                                        : url('/tracking/' . $order->public_tracking_token))
                                    : null;
                            @endphp
                            <tr class="hover:bg-[#F8FAFC]/80 align-top">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-[#142B4A]">{{ $order->order_number ?? ('ORD-' . $order->id) }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        {{ $order->created_at?->format('d M Y H:i') ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold bg-[#F8FAFC] text-[#142B4A] border border-[#DCE3EA]">
                                        {{ $order->delivery_type ?? 'delivery' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $orderBadge['bg'] }} {{ $orderBadge['text'] }} {{ $orderBadge['border'] }}">
                                        {{ $orderBadge['label'] }}
                                    </span>
                                    @if (! empty($order->shipment_status))
                                        <div class="text-[11px] text-slate-500 mt-1">
                                            Shipment: <span class="font-semibold text-[#142B4A]">{{ $order->shipment_status }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($orderShipment)
                                        <div class="font-semibold text-[#142B4A]">{{ $orderShipment->carrier }} {{ $orderShipment->service ? '(' . $orderShipment->service . ')' : '' }}</div>
                                        <div class="font-mono text-xs text-[#2563EB] mt-0.5">{{ $orderShipment->tracking_number ?? '-' }}</div>
                                    @else
                                        <span class="text-xs text-slate-400">Belum ada resi</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($publicTrackUrl)
                                        <a href="{{ $publicTrackUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#2563EB] hover:text-[#142B4A] underline decoration-[#2563EB]/40">
                                            <span>Buka Tracking</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 min-w-[320px]">
                                    @if (! in_array($order->status, ['delivered', 'completed'], true))
                                        <form method="POST" action="{{ $dispatchAction }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @csrf
                                            <input type="text" name="carrier" value="{{ old('carrier', $orderShipment->carrier ?? '') }}"
                                                   placeholder="Kurir (cth: JNE, SiCepat)" required
                                                   class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-xs text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            <input type="text" name="service" value="{{ old('service', $orderShipment->service ?? '') }}"
                                                   placeholder="Layanan (REG/YES)"
                                                   class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-xs text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            <input type="text" name="tracking_number" value="{{ old('tracking_number', $orderShipment->tracking_number ?? '') }}"
                                                   placeholder="Nomor Resi" required
                                                   class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-xs text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            <input type="datetime-local" name="estimated_delivery_at"
                                                   value="{{ old('estimated_delivery_at', $orderShipment?->estimated_delivery_at?->format('Y-m-d\TH:i') ?? '') }}"
                                                   class="rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-xs text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            <input type="text" name="notes" value="{{ old('notes', $orderShipment->notes ?? '') }}"
                                                   placeholder="Catatan pengiriman (opsional)"
                                                   class="sm:col-span-2 rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-xs text-[#142B4A] focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            <div class="sm:col-span-2 flex justify-end">
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-[#2563EB] hover:bg-[#142B4A] transition-colors">
                                                    {{ $orderShipment ? 'Perbarui Resi & Kirim' : 'Kirim Pesanan' }}
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0F766E]">
                                            <svg class="w-4 h-4 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Pengiriman Selesai
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">
                                    Belum ada pesanan siap kirim saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="px-5 py-3 border-t border-[#DCE3EA] bg-[#F8FAFC]">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

        <!-- Daftar Shipment & Konfirmasi Penerimaan (POD) -->
        <div class="bg-white rounded-xl border border-[#DCE3EA] shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-[#DCE3EA] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-[#F8FAFC]">
                <div>
                    <h3 class="text-base font-bold text-[#142B4A]">Daftar Pengiriman &amp; Konfirmasi Penerimaan</h3>
                    <p class="text-xs text-slate-500">Pantau status pengiriman aktif dan unggah bukti terima (Proof of Delivery).</p>
                </div>
                <span class="text-xs font-semibold text-[#142B4A] bg-white border border-[#DCE3EA] px-3 py-1 rounded-lg">
                    Total: {{ $shipments->total() }} pengiriman
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#F8FAFC] text-xs font-bold uppercase tracking-wider text-[#142B4A]">
                        <tr>
                            <th class="px-5 py-3 text-left">Shipment / Order</th>
                            <th class="px-5 py-3 text-left">Kurir &amp; Resi</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Timeline Waktu</th>
                            <th class="px-5 py-3 text-left">Konfirmasi Penerimaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($shipments as $shipment)
                            @php
                                $shipBadge = $statusStyles[$shipment->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'label' => ucfirst((string) $shipment->status)];
                                $confirmAction = \Illuminate\Support\Facades\Route::has('shipping.confirm-delivery')
                                    ? route('shipping.confirm-delivery', $shipment)
                                    : url('/shipping/' . $shipment->id . '/confirm-delivery');
                            @endphp
                            <tr class="hover:bg-[#F8FAFC]/80 align-top">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-[#142B4A]">#SHP-{{ $shipment->id }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        Order: <span class="font-semibold text-[#142B4A]">{{ $shipment->order?->order_number ?? ('#' . $shipment->order_id) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[#142B4A]">
                                        {{ $shipment->carrier }}
                                        @if ($shipment->service)
                                            <span class="text-xs text-slate-500">({{ $shipment->service }})</span>
                                        @endif
                                    </div>
                                    <div class="font-mono text-xs text-[#2563EB] mt-0.5">{{ $shipment->tracking_number ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $shipBadge['bg'] }} {{ $shipBadge['text'] }} {{ $shipBadge['border'] }}">
                                        {{ $shipBadge['label'] }}
                                    </span>
                                    @if ($shipment->events->isNotEmpty())
                                        <div class="text-[11px] text-slate-500 mt-1">
                                            {{ $shipment->events->last()->description ?? $shipment->events->last()->status }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-600 space-y-1">
                                    <div>Dikirim: <span class="font-semibold text-[#142B4A]">{{ $shipment->shipped_at?->format('d M Y H:i') ?? '-' }}</span></div>
                                    <div>Estimasi: <span class="font-semibold text-[#142B4A]">{{ $shipment->estimated_delivery_at?->format('d M Y H:i') ?? '-' }}</span></div>
                                    <div>Diterima: <span class="font-semibold text-[#0F766E]">{{ $shipment->delivered_at?->format('d M Y H:i') ?? '-' }}</span></div>
                                </td>
                                <td class="px-5 py-4 min-w-[290px]">
                                    @if ($shipment->status !== 'delivered')
                                        <form method="POST" action="{{ $confirmAction }}" enctype="multipart/form-data" class="space-y-2">
                                            @csrf
                                            <div>
                                                <label class="block text-[11px] font-semibold text-[#142B4A] mb-1">Bukti Terima (JPG/PNG/WEBP/PDF)</label>
                                                <input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                       class="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border file:border-[#DCE3EA] file:text-xs file:font-semibold file:bg-[#F8FAFC] file:text-[#142B4A] hover:file:bg-slate-100" />
                                            </div>
                                            <input type="text" name="notes" placeholder="Catatan penerimaan (opsional)"
                                                   class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1.5 text-xs text-[#142B4A] focus:border-[#10B981] focus:ring-[#10B981]" />
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-[#10B981] hover:bg-[#0F766E] transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Konfirmasi Diterima
                                            </button>
                                        </form>
                                    @else
                                        <div class="space-y-1 text-xs">
                                            <div class="font-semibold text-[#0F766E]">
                                                Dikonfirmasi oleh {{ $shipment->confirmer?->name ?? 'User' }}
                                                <span class="uppercase text-[10px] px-1.5 py-0.5 rounded bg-[#10B981]/10 border border-[#10B981]/30">
                                                    {{ $shipment->confirmation_source ?? 'admin' }}
                                                </span>
                                            </div>
                                            @if ($shipment->proof_of_delivery_path)
                                                <div class="text-slate-500">POD tersimpan</div>
                                            @endif
                                            @if ($shipment->notes)
                                                <div class="text-slate-500 italic">{{ $shipment->notes }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">
                                    Belum ada data pengiriman tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($shipments->hasPages())
                <div class="px-5 py-3 border-t border-[#DCE3EA] bg-[#F8FAFC]">
                    {{ $shipments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
