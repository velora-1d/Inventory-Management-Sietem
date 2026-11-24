<x-guest-layout :title="'Lacak Pesanan ' . ($tracking['order_number'] ?? '')">
    @php
        $statusMap = [
            'pending' => ['bg' => 'bg-[#D97706]/10', 'text' => 'text-[#D97706]', 'border' => 'border-[#D97706]/30', 'label' => 'Menunggu'],
            'confirmed' => ['bg' => 'bg-[#2563EB]/10', 'text' => 'text-[#2563EB]', 'border' => 'border-[#2563EB]/30', 'label' => 'Dikonfirmasi'],
            'processing' => ['bg' => 'bg-[#2563EB]/10', 'text' => 'text-[#2563EB]', 'border' => 'border-[#2563EB]/30', 'label' => 'Diproses'],
            'ready_to_ship' => ['bg' => 'bg-[#0F766E]/10', 'text' => 'text-[#0F766E]', 'border' => 'border-[#0F766E]/30', 'label' => 'Siap Dikirim'],
            'shipped' => ['bg' => 'bg-[#2563EB]/10', 'text' => 'text-[#2563EB]', 'border' => 'border-[#2563EB]/30', 'label' => 'Dalam Pengiriman'],
            'in_transit' => ['bg' => 'bg-[#D97706]/10', 'text' => 'text-[#D97706]', 'border' => 'border-[#D97706]/30', 'label' => 'Dalam Perjalanan'],
            'delivered' => ['bg' => 'bg-[#10B981]/10', 'text' => 'text-[#0F766E]', 'border' => 'border-[#10B981]/30', 'label' => 'Diterima'],
            'completed' => ['bg' => 'bg-[#10B981]/10', 'text' => 'text-[#0F766E]', 'border' => 'border-[#10B981]/30', 'label' => 'Selesai'],
            'cancelled' => ['bg' => 'bg-[#DC2626]/10', 'text' => 'text-[#DC2626]', 'border' => 'border-[#DC2626]/30', 'label' => 'Dibatalkan'],
            'failed' => ['bg' => 'bg-[#DC2626]/10', 'text' => 'text-[#DC2626]', 'border' => 'border-[#DC2626]/30', 'label' => 'Gagal'],
        ];

        $currentStatus = $tracking['status'] ?? 'pending';
        $badge = $statusMap[$currentStatus] ?? [
            'bg' => 'bg-slate-100',
            'text' => 'text-[#142B4A]',
            'border' => 'border-[#DCE3EA]',
            'label' => ucfirst((string) $currentStatus),
        ];

        $milestones = $tracking['milestones'] ?? [];
        $milestoneCount = count($milestones);
    @endphp

    <div class="min-h-screen bg-[#F8FAFC] text-[#142B4A] py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Brand Header -->
            <header class="bg-white rounded-2xl border border-[#DCE3EA] shadow-sm px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <x-application-logo class="w-11 h-11 shrink-0" />
                    <div>
                        <h1 class="text-lg font-extrabold text-[#142B4A] leading-tight">
                            {{ brand_name() }}
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ brand_subtitle() }}
                        </p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-2 self-start sm:self-center px-3 py-1.5 rounded-lg text-xs font-bold bg-[#142B4A] text-white">
                    <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                    Public Order Tracking
                </span>
            </header>

            <!-- Order & Shipment Summary -->
            <section class="bg-white rounded-2xl border border-[#DCE3EA] shadow-sm p-6 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-[#DCE3EA] pb-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nomor Pesanan</div>
                        <div class="text-2xl font-extrabold text-[#142B4A] font-mono mt-0.5">
                            {{ $tracking['order_number'] ?? '-' }}
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['border'] }}">
                            {{ $badge['label'] }}
                        </span>
                        @if (! empty($tracking['delivery_type']))
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#F8FAFC] text-[#142B4A] border border-[#DCE3EA]">
                                {{ ucfirst(str_replace('_', ' ', (string) $tracking['delivery_type'])) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-xl bg-[#F8FAFC] border border-[#DCE3EA] p-3.5">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status Pengiriman</div>
                        <div class="text-sm font-bold text-[#142B4A] mt-1">
                            {{ ! empty($tracking['shipment_status']) ? ucfirst(str_replace('_', ' ', (string) $tracking['shipment_status'])) : 'Menunggu Pengiriman' }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-[#F8FAFC] border border-[#DCE3EA] p-3.5">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Kurir &amp; Layanan</div>
                        <div class="text-sm font-bold text-[#142B4A] mt-1">
                            @if (! empty($tracking['carrier']))
                                {{ $tracking['carrier'] }}{{ ! empty($tracking['service']) ? ' • ' . $tracking['service'] : '' }}
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl bg-[#F8FAFC] border border-[#DCE3EA] p-3.5">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Nomor Resi</div>
                        <div class="text-sm font-bold font-mono text-[#2563EB] mt-1 break-all">
                            {{ $tracking['tracking_number'] ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-[#F8FAFC] border border-[#DCE3EA] p-3.5">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            {{ ! empty($tracking['delivered_at']) ? 'Diterima Pada' : 'Estimasi Tiba' }}
                        </div>
                        <div class="text-sm font-bold {{ ! empty($tracking['delivered_at']) ? 'text-[#0F766E]' : 'text-[#142B4A]' }} mt-1">
                            @if (! empty($tracking['delivered_at']))
                                {{ \Illuminate\Support\Carbon::parse($tracking['delivered_at'])->format('d M Y, H:i') }}
                            @elseif (! empty($tracking['estimated_delivery_at']))
                                {{ \Illuminate\Support\Carbon::parse($tracking['estimated_delivery_at'])->format('d M Y, H:i') }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <!-- Public Milestone Stepper Timeline -->
            <section class="bg-white rounded-2xl border border-[#DCE3EA] shadow-sm p-6">
                <div class="flex items-center justify-between border-b border-[#DCE3EA] pb-4 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-[#142B4A]">Linimasa Status Pesanan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pembaruan milestone publik tanpa menampilkan data pribadi pelanggan.</p>
                    </div>
                    <span class="text-xs font-semibold text-[#0F766E] bg-[#10B981]/10 border border-[#10B981]/25 px-2.5 py-1 rounded-lg">
                        {{ $milestoneCount }} Tahapan
                    </span>
                </div>

                @if ($milestoneCount > 0)
                    <ol class="relative space-y-6">
                        @foreach ($milestones as $index => $milestone)
                            @php
                                $isLatest = $index === $milestoneCount - 1;
                                $isCompleted = in_array($milestone['milestone_code'] ?? '', ['delivered', 'completed'], true);
                                $dotClass = $isCompleted
                                    ? 'bg-[#10B981] border-[#10B981] text-white'
                                    : ($isLatest
                                        ? 'bg-[#2563EB] border-[#2563EB] text-white ring-4 ring-[#2563EB]/15'
                                        : 'bg-[#0F766E] border-[#0F766E] text-white');
                            @endphp
                            <li class="relative pl-9">
                                @if (! $loop->last)
                                    <span class="absolute left-[11px] top-6 -bottom-6 w-0.5 bg-[#DCE3EA]" aria-hidden="true"></span>
                                @endif

                                <span class="absolute left-0 top-0.5 w-6 h-6 rounded-full border-2 flex items-center justify-center {{ $dotClass }}">
                                    @if ($isCompleted || ! $isLatest)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                    @endif
                                </span>

                                <div class="rounded-xl border {{ $isLatest ? 'border-[#2563EB]/30 bg-[#2563EB]/5' : 'border-[#DCE3EA] bg-[#F8FAFC]' }} px-4 py-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                        <h3 class="text-sm font-bold text-[#142B4A]">
                                            {{ $milestone['title'] ?? '-' }}
                                        </h3>
                                        @if (! empty($milestone['occurred_at']))
                                            <time class="text-xs font-medium text-slate-500">
                                                {{ \Illuminate\Support\Carbon::parse($milestone['occurred_at'])->format('d M Y, H:i') }}
                                            </time>
                                        @endif
                                    </div>
                                    @if (! empty($milestone['description']))
                                        <p class="text-xs text-slate-600 mt-1">
                                            {{ $milestone['description'] }}
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="rounded-xl border border-dashed border-[#DCE3EA] bg-[#F8FAFC] p-6 text-center text-sm text-slate-500">
                        Belum ada pembaruan status publik untuk pesanan ini.
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-guest-layout>
