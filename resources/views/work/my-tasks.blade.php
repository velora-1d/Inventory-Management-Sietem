@php
    $boardUrl = \Illuminate\Support\Facades\Route::has('work.board') ? route('work.board') : url('/work/board');
    $myTasksUrl = \Illuminate\Support\Facades\Route::has('work.my-tasks') ? route('work.my-tasks') : url('/work/my-tasks');
    $allTasksUrl = \Illuminate\Support\Facades\Route::has('work.tasks') ? route('work.tasks') : url('/work/tasks');
@endphp

<x-app-layout title="My Tasks">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    My Assigned Tasks
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Personal execution queue for {{ Auth::user()->name ?? 'Staff' }} with SLA tracking and dependencies.
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

    <div class="space-y-6" x-data="{
        completeModalOpen: false,
        revisionModalOpen: false,
        activeTitle: '',
        completeUrl: '',
        revisionUrl: '',
        openComplete(title, url) {
            this.activeTitle = title;
            this.completeUrl = url;
            this.completeModalOpen = true;
        },
        openRevision(title, url) {
            this.activeTitle = title;
            this.revisionUrl = url;
            this.revisionModalOpen = true;
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

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Assigned</p>
                <p class="text-2xl font-bold text-[#142B4A] mt-1">{{ number_format($summary['total']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Queue</p>
                <p class="text-2xl font-bold text-[#2563EB] mt-1">{{ number_format($summary['active']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Needs Revision</p>
                <p class="text-2xl font-bold text-[#D97706] mt-1">{{ number_format($summary['revision']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Completed</p>
                <p class="text-2xl font-bold text-[#10B981] mt-1">{{ number_format($summary['completed']) }}</p>
            </div>
        </div>

        {{-- Status Filter --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
            <form method="GET" action="{{ $myTasksUrl }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-56">
                    <label for="my_status" class="block text-xs font-semibold text-[#142B4A] mb-1">Filter Status</label>
                    <select id="my_status"
                            name="status"
                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">All My Tasks</option>
                        <option value="assigned" @selected($status === 'assigned')>Assigned</option>
                        <option value="in_progress" @selected($status === 'in_progress')>In Progress</option>
                        <option value="revision" @selected($status === 'revision')>Revision</option>
                        <option value="completed" @selected($status === 'completed')>Completed</option>
                    </select>
                </div>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold transition-colors">
                    Apply
                </button>
                <a href="{{ $myTasksUrl }}"
                   class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-slate-600 text-sm font-medium transition-colors">
                    Reset
                </a>
            </form>
        </div>

        {{-- Tasks Table --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#DCE3EA] text-sm">
                    <thead class="bg-[#142B4A] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Task / Order</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Department</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">SLA</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Dependencies</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCE3EA] bg-white">
                        @forelse ($tasks as $task)
                            @php
                                $sla = $task->slaStatus();
                                $blocked = $task->isBlocked();
                                $startAction = \Illuminate\Support\Facades\Route::has('work.start') ? route('work.start', $task) : url("/work/tasks/{$task->id}/start");
                                $completeAction = \Illuminate\Support\Facades\Route::has('work.complete') ? route('work.complete', $task) : url("/work/tasks/{$task->id}/complete");
                                $revisionAction = \Illuminate\Support\Facades\Route::has('work.revision') ? route('work.revision', $task) : url("/work/tasks/{$task->id}/revision");
                            @endphp
                            <tr class="hover:bg-[#F8FAFC] transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-[#142B4A]">{{ $task->title }}</div>
                                    <div class="text-xs text-slate-500 font-mono">
                                        Order #{{ $task->order?->order_number ?? $task->order_id }} &bull; {{ $task->code ?? '-' }}
                                    </div>
                                    @if ($task->notes)
                                        <p class="text-xs text-slate-500 mt-1">{{ \Illuminate\Support\Str::limit($task->notes, 100) }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700 font-medium">
                                    {{ $task->department }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $task->status === 'completed' ? 'bg-emerald-50 text-[#0F766E] border-emerald-200' : ($task->status === 'revision' ? 'bg-amber-50 text-[#D97706] border-amber-200' : 'bg-blue-50 text-[#2563EB] border-blue-200') }}">
                                        {{ ucwords(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($sla === 'overdue')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-[#DC2626] border border-red-200">
                                            Overdue
                                        </span>
                                    @elseif ($sla === 'warning')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-[#D97706] border border-amber-200">
                                            Warning
                                        </span>
                                    @elseif ($sla === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-[#0F766E] border border-emerald-200">
                                            Completed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-[#2563EB] border border-blue-200">
                                            On Track
                                        </span>
                                    @endif
                                    @if ($task->due_at)
                                        <div class="text-[11px] font-mono text-slate-400 mt-0.5">{{ $task->due_at->format('d M H:i') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($task->dependencies->isEmpty())
                                        <span class="text-xs text-slate-400">None</span>
                                    @else
                                        <div class="space-y-1">
                                            @foreach ($task->dependencies as $dep)
                                                <div class="text-xs {{ $dep->status === 'completed' ? 'text-[#10B981]' : 'text-[#DC2626] font-semibold' }}">
                                                    {{ $dep->title }} ({{ $dep->status }})
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($task->status !== \App\Models\OrderTask::STATUS_COMPLETED)
                                        <div class="inline-flex items-center gap-1.5">
                                            @if (in_array($task->status, [\App\Models\OrderTask::STATUS_PENDING, \App\Models\OrderTask::STATUS_ASSIGNED, \App\Models\OrderTask::STATUS_REVISION], true))
                                                <form method="POST" action="{{ $startAction }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            @disabled($blocked)
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ $blocked ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-[#2563EB] hover:bg-[#1D4ED8] text-white' }}">
                                                        Start
                                                    </button>
                                                </form>
                                            @endif
                                            <button type="button"
                                                    @disabled($blocked)
                                                    @click="openComplete(@js($task->title), @js($completeAction))"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold {{ $blocked ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-[#10B981] hover:bg-[#0F766E] text-white' }}">
                                                Complete
                                            </button>
                                            <button type="button"
                                                    @click="openRevision(@js($task->title), @js($revisionAction))"
                                                    class="px-2.5 py-1.5 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 text-[#D97706] text-xs font-semibold">
                                                Revision
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs font-medium text-[#0F766E]">Done {{ $task->completed_at?->format('d M H:i') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    No tasks are currently assigned to you.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tasks->hasPages())
                <div class="px-4 py-3 border-t border-[#DCE3EA] bg-[#F8FAFC]">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>

        {{-- Complete Modal --}}
        <div x-show="completeModalOpen" x-cloak style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="completeModalOpen = false"
                 class="w-full max-w-md bg-white border border-[#DCE3EA] rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 bg-[#142B4A] text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Complete Task</h3>
                        <p class="text-xs text-sky-200" x-text="activeTitle"></p>
                    </div>
                    <button type="button" @click="completeModalOpen = false" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
                </div>
                <form method="POST" :action="completeUrl" class="p-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Completion Note (Optional)</label>
                        <textarea name="note" rows="3" placeholder="e.g. All items verified and packed."
                                  class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-[#DCE3EA]">
                        <button type="button" @click="completeModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600 hover:bg-[#F8FAFC]">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-[#10B981] hover:bg-[#0F766E] text-white text-sm font-semibold">
                            Confirm Complete
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Revision Modal --}}
        <div x-show="revisionModalOpen" x-cloak style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="revisionModalOpen = false"
                 class="w-full max-w-md bg-white border border-[#DCE3EA] rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 bg-[#142B4A] text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Request Revision</h3>
                        <p class="text-xs text-sky-200" x-text="activeTitle"></p>
                    </div>
                    <button type="button" @click="revisionModalOpen = false" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
                </div>
                <form method="POST" :action="revisionUrl" enctype="multipart/form-data" class="p-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Revision Reason (Mandatory)</label>
                        <textarea name="reason" rows="3" required minlength="3" placeholder="Describe discrepancy or damage..."
                                  class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Attachment (Optional)</label>
                        <input type="file" name="attachment"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-[#DCE3EA] file:bg-[#F8FAFC] file:text-[#142B4A] file:font-semibold" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-[#DCE3EA]">
                        <button type="button" @click="revisionModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600 hover:bg-[#F8FAFC]">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-[#D97706] hover:bg-amber-700 text-white text-sm font-semibold">
                            Submit Revision
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
