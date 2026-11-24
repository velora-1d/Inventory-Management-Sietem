@php
    $boardUrl = \Illuminate\Support\Facades\Route::has('work.board') ? route('work.board') : url('/work/board');
    $allTasksUrl = \Illuminate\Support\Facades\Route::has('work.tasks') ? route('work.tasks') : url('/work/tasks');
    $storeStepUrl = \Illuminate\Support\Facades\Route::has('work.workflows.step.store') ? route('work.workflows.step.store') : url('/work/workflows/steps');
@endphp

<x-app-layout title="Workflows & SLA">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Workflow Definitions &amp; SLA Rules
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Configure sequential fulfillment steps, role auto-assignment, SLA timers, and public tracking milestones.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $boardUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    Kanban Board
                </a>
                <a href="{{ $allTasksUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-[#142B4A] text-white hover:bg-[#0F223B] transition-colors">
                    All Tasks
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-[#0F766E] font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-[#DC2626]">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Existing Workflows --}}
        @forelse ($workflows as $workflow)
            <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-[#142B4A] text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold">{{ $workflow->name }}</h3>
                            @if ($workflow->is_default)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#10B981] text-white">DEFAULT</span>
                            @endif
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $workflow->is_active ? 'bg-[#2563EB] text-white' : 'bg-slate-600 text-slate-200' }}">
                                {{ $workflow->is_active ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                        </div>
                        <p class="text-xs text-sky-200 mt-0.5">
                            Code: <span class="font-mono">{{ $workflow->code }}</span>
                            @if ($workflow->description)
                                &mdash; {{ $workflow->description }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                        <thead class="bg-[#F8FAFC] text-[#142B4A]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Step</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Department</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Default Role</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">SLA</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Depends On</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Public Milestone</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCE3EA] bg-white">
                            @forelse ($workflow->steps as $step)
                                <tr class="hover:bg-[#F8FAFC]">
                                    <td class="px-4 py-3 font-mono font-bold text-[#142B4A]">#{{ $step->step_order }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-[#142B4A]">{{ $step->name }}</div>
                                        <div class="text-xs font-mono text-slate-500">{{ $step->code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $step->department }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-md bg-blue-50 text-[#2563EB] border border-blue-200 text-xs font-semibold font-mono">
                                            {{ $step->default_role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-[#142B4A]">
                                        {{ $step->sla_minutes }} min
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $step->dependsOn?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($step->is_public_milestone)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-[#0F766E] border border-emerald-200">
                                                {{ $step->public_milestone_title ?: 'Public' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">Internal only</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-400">
                                        No steps configured for this workflow.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-8 text-center text-slate-500">
                No workflows found. Run WorkflowSeeder to initialize the default fulfillment pipeline.
            </div>
        @endforelse

        {{-- Add Step Form --}}
        @if ($workflows->isNotEmpty())
            <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-[#F8FAFC] border-b border-[#DCE3EA]">
                    <h3 class="text-sm font-bold text-[#142B4A]">Add Workflow Step</h3>
                    <p class="text-xs text-slate-500">Append a new task template step into an existing workflow.</p>
                </div>
                <form method="POST" action="{{ $storeStepUrl }}" class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Workflow</label>
                        <select name="order_workflow_id" required
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                            @foreach ($workflows as $wf)
                                <option value="{{ $wf->id }}">{{ $wf->name }} ({{ $wf->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Step Order</label>
                        <input type="number" name="step_order" min="1" value="{{ old('step_order', 5) }}" required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">SLA Minutes</label>
                        <input type="number" name="sla_minutes" min="1" value="{{ old('sla_minutes', 20) }}" required
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Step Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Final Barcode Check"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Step Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="e.g. barcode_check"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Department</label>
                        <input type="text" name="department" value="{{ old('department') }}" required placeholder="e.g. QC & Packing"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Default Role</label>
                        <select name="default_role" required
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                            @foreach ($roles as $roleName)
                                <option value="{{ $roleName }}" @selected(old('default_role') === $roleName)>{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Depends On Step (Optional)</label>
                        <select name="depends_on_step_id"
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                            <option value="">— No Dependency —</option>
                            @foreach ($workflows as $wf)
                                @foreach ($wf->steps as $st)
                                    <option value="{{ $st->id }}">{{ $wf->code }} #{{ $st->step_order }}: {{ $st->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Public Milestone Title (Optional)</label>
                        <input type="text" name="public_milestone_title" value="{{ old('public_milestone_title') }}"
                               placeholder="e.g. Pesanan diverifikasi"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>

                    <div class="md:col-span-3 flex items-center justify-between pt-2 border-t border-[#DCE3EA]">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-[#142B4A]">
                            <input type="checkbox" name="is_public_milestone" value="1"
                                   class="rounded border-[#DCE3EA] text-[#2563EB] focus:ring-[#2563EB]" />
                            Expose as public order tracking milestone
                        </label>
                        <button type="submit"
                                class="px-5 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold transition-colors">
                            + Add Workflow Step
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
