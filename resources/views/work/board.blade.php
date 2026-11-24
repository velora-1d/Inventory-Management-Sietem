@php
    $boardUrl = \Illuminate\Support\Facades\Route::has('work.board') ? route('work.board') : url('/work/board');
    $myTasksUrl = \Illuminate\Support\Facades\Route::has('work.my-tasks') ? route('work.my-tasks') : url('/work/my-tasks');
    $allTasksUrl = \Illuminate\Support\Facades\Route::has('work.tasks') ? route('work.tasks') : url('/work/tasks');
    $workflowsUrl = \Illuminate\Support\Facades\Route::has('work.workflows') ? route('work.workflows') : url('/work/workflows');

    $columnMeta = [
        'pending_assigned' => [
            'title' => 'Pending / Assigned',
            'badgeBg' => 'bg-slate-100 text-[#142B4A] border-[#DCE3EA]',
            'accent' => 'border-t-4 border-t-[#142B4A]',
        ],
        'in_progress' => [
            'title' => 'In Progress',
            'badgeBg' => 'bg-blue-50 text-[#2563EB] border-blue-200',
            'accent' => 'border-t-4 border-t-[#2563EB]',
        ],
        'revision' => [
            'title' => 'Revision',
            'badgeBg' => 'bg-amber-50 text-[#D97706] border-amber-200',
            'accent' => 'border-t-4 border-t-[#D97706]',
        ],
        'completed' => [
            'title' => 'Completed',
            'badgeBg' => 'bg-emerald-50 text-[#0F766E] border-emerald-200',
            'accent' => 'border-t-4 border-t-[#10B981]',
        ],
    ];
@endphp

<x-app-layout title="Work Kanban Board">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-[#142B4A] leading-tight">
                    Work Management — Kanban Board
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Real-time fulfillment pipeline across Warehouse, QC &amp; Packing, and Shipping departments.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $myTasksUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    My Tasks
                </a>
                <a href="{{ $allTasksUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#DCE3EA] bg-white text-[#142B4A] hover:bg-[#F8FAFC] transition-colors">
                    All Tasks
                </a>
                <a href="{{ $workflowsUrl }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-[#0F766E] bg-[#0F766E] text-white hover:bg-[#0D655E] transition-colors">
                    Workflows &amp; SLA
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        revisionModalOpen: false,
        reassignModalOpen: false,
        activeTaskId: null,
        activeTaskTitle: '',
        revisionUrl: '',
        reassignUrl: '',
        openRevision(id, title, url) {
            this.activeTaskId = id;
            this.activeTaskTitle = title;
            this.revisionUrl = url;
            this.revisionModalOpen = true;
        },
        openReassign(id, title, url) {
            this.activeTaskId = id;
            this.activeTaskTitle = title;
            this.reassignUrl = url;
            this.reassignModalOpen = true;
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

        {{-- Summary KPIs --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Tasks</p>
                <p class="text-2xl font-bold text-[#142B4A] mt-1">{{ number_format($summary['total']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Queued</p>
                <p class="text-2xl font-bold text-[#142B4A] mt-1">{{ number_format($summary['pending_assigned']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">In Progress</p>
                <p class="text-2xl font-bold text-[#2563EB] mt-1">{{ number_format($summary['in_progress']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Revision</p>
                <p class="text-2xl font-bold text-[#D97706] mt-1">{{ number_format($summary['revision']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Completed</p>
                <p class="text-2xl font-bold text-[#10B981] mt-1">{{ number_format($summary['completed']) }}</p>
            </div>
            <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">SLA Overdue</p>
                <p class="text-2xl font-bold text-[#DC2626] mt-1">{{ number_format($summary['overdue']) }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white border border-[#DCE3EA] rounded-xl p-4 shadow-sm">
            <form method="GET" action="{{ $boardUrl }}" class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1">
                    <label for="board_search" class="block text-xs font-semibold text-[#142B4A] mb-1">Search Task / Code</label>
                    <input id="board_search"
                           type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search task title, code, or department..."
                           class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                </div>

                <div class="w-full lg:w-48">
                    <label for="board_department" class="block text-xs font-semibold text-[#142B4A] mb-1">Department</label>
                    <select id="board_department"
                            name="department"
                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}" @selected($department === $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full lg:w-40">
                    <label for="board_priority" class="block text-xs font-semibold text-[#142B4A] mb-1">Priority</label>
                    <select id="board_priority"
                            name="priority"
                            class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                        <option value="">All Priorities</option>
                        <option value="low" @selected($priority === 'low')>Low</option>
                        <option value="normal" @selected($priority === 'normal')>Normal</option>
                        <option value="high" @selected($priority === 'high')>High</option>
                        <option value="urgent" @selected($priority === 'urgent')>Urgent</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold transition-colors">
                        Filter
                    </button>
                    <a href="{{ $boardUrl }}"
                       class="px-3.5 py-2 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-slate-600 text-sm font-medium transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Kanban Columns --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 items-start">
            @foreach ($columnMeta as $colKey => $meta)
                @php
                    $colTasks = $columns[$colKey] ?? collect();
                @endphp
                <div class="bg-white border border-[#DCE3EA] rounded-xl shadow-sm overflow-hidden {{ $meta['accent'] }}">
                    <div class="px-4 py-3 bg-[#F8FAFC] border-b border-[#DCE3EA] flex items-center justify-between">
                        <h3 class="text-sm font-bold text-[#142B4A]">{{ $meta['title'] }}</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $meta['badgeBg'] }}">
                            {{ $colTasks->count() }}
                        </span>
                    </div>

                    <div class="p-3 space-y-3 max-h-[72vh] overflow-y-auto bg-[#F8FAFC]/50">
                        @forelse ($colTasks as $task)
                            @php
                                $sla = $task->slaStatus();
                                $blocked = $task->isBlocked();
                                $startAction = \Illuminate\Support\Facades\Route::has('work.start') ? route('work.start', $task) : url("/work/tasks/{$task->id}/start");
                                $completeAction = \Illuminate\Support\Facades\Route::has('work.complete') ? route('work.complete', $task) : url("/work/tasks/{$task->id}/complete");
                                $revisionAction = \Illuminate\Support\Facades\Route::has('work.revision') ? route('work.revision', $task) : url("/work/tasks/{$task->id}/revision");
                                $reassignAction = \Illuminate\Support\Facades\Route::has('work.reassign') ? route('work.reassign', $task) : url("/work/tasks/{$task->id}/reassign");
                                $commentAction = \Illuminate\Support\Facades\Route::has('work.comment') ? route('work.comment', $task) : url("/work/tasks/{$task->id}/comment");
                            @endphp
                            <div class="bg-white border border-[#DCE3EA] rounded-xl p-3.5 shadow-sm space-y-2.5" x-data="{ showComments: false }">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="text-[11px] font-mono font-semibold text-[#2563EB]">
                                            Order #{{ $task->order?->order_number ?? $task->order_id }}
                                        </span>
                                        <h4 class="text-sm font-bold text-[#142B4A] leading-snug">
                                            {{ $task->title }}
                                        </h4>
                                    </div>
                                    @if ($sla === 'overdue')
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-[#DC2626] border border-red-200">
                                            Overdue
                                        </span>
                                    @elseif ($sla === 'warning')
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-[#D97706] border border-amber-200">
                                            SLA Soon
                                        </span>
                                    @elseif ($sla === 'completed')
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-[#0F766E] border border-emerald-200">
                                            Done
                                        </span>
                                    @else
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#2563EB] border border-blue-200">
                                            On Track
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                                    <span class="px-2 py-0.5 rounded-md bg-[#F8FAFC] border border-[#DCE3EA] text-[#142B4A] font-medium">
                                        {{ $task->department }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium capitalize">
                                        {{ str_replace('_', ' ', $task->status) }}
                                    </span>
                                    @if ($blocked)
                                        <span class="px-2 py-0.5 rounded-md bg-red-50 border border-red-200 text-[#DC2626] font-semibold">
                                            Blocked
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs text-slate-600 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Assignee:</span>
                                        <span class="font-semibold text-[#142B4A]">{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                                    </div>
                                    @if ($task->due_at)
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Due:</span>
                                            <span class="font-mono text-[11px] {{ $sla === 'overdue' ? 'text-[#DC2626] font-bold' : 'text-slate-600' }}">
                                                {{ $task->due_at->format('d M H:i') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                @if ($task->notes)
                                    <p class="text-[11px] text-slate-600 bg-[#F8FAFC] border border-[#DCE3EA] rounded-lg p-2 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($task->notes, 140) }}</p>
                                @endif

                                {{-- Action Buttons --}}
                                @if ($task->status !== \App\Models\OrderTask::STATUS_COMPLETED)
                                    <div class="pt-2 border-t border-[#DCE3EA] flex flex-wrap gap-1.5">
                                        @if (in_array($task->status, [\App\Models\OrderTask::STATUS_PENDING, \App\Models\OrderTask::STATUS_ASSIGNED, \App\Models\OrderTask::STATUS_REVISION], true))
                                            <form method="POST" action="{{ $startAction }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        @disabled($blocked)
                                                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold {{ $blocked ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-[#2563EB] hover:bg-[#1D4ED8] text-white' }}">
                                                    Start
                                                </button>
                                            </form>
                                        @endif

                                        @if (in_array($task->status, [\App\Models\OrderTask::STATUS_ASSIGNED, \App\Models\OrderTask::STATUS_IN_PROGRESS, \App\Models\OrderTask::STATUS_REVIEW, \App\Models\OrderTask::STATUS_REVISION], true))
                                            <form method="POST" action="{{ $completeAction }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        @disabled($blocked)
                                                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold {{ $blocked ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-[#10B981] hover:bg-[#0F766E] text-white' }}">
                                                    Complete
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button"
                                                @click="openRevision({{ $task->id }}, @js($task->title), @js($revisionAction))"
                                                class="px-2.5 py-1 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 text-[#D97706] text-[11px] font-semibold">
                                            Revision
                                        </button>

                                        <button type="button"
                                                @click="openReassign({{ $task->id }}, @js($task->title), @js($reassignAction))"
                                                class="px-2.5 py-1 rounded-lg border border-[#DCE3EA] bg-white hover:bg-[#F8FAFC] text-[#142B4A] text-[11px] font-semibold">
                                            Reassign
                                        </button>
                                    </div>
                                @endif

                                {{-- Comments Toggle --}}
                                <div class="pt-1">
                                    <button type="button"
                                            @click="showComments = !showComments"
                                            class="text-[11px] font-semibold text-[#2563EB] hover:underline">
                                        Comments ({{ $task->comments->count() }}) &bull; Attachments ({{ $task->attachments->count() }})
                                    </button>
                                    <div x-show="showComments" x-cloak style="display: none;" class="mt-2 space-y-2 border-t border-[#DCE3EA] pt-2">
                                        @foreach ($task->comments->take(3) as $comment)
                                            <div class="text-[11px] bg-[#F8FAFC] rounded-lg p-2 border border-[#DCE3EA]">
                                                <span class="font-semibold text-[#142B4A]">{{ $comment->user?->name ?? 'User' }}:</span>
                                                <span class="text-slate-600">{{ $comment->body }}</span>
                                            </div>
                                        @endforeach
                                        <form method="POST" action="{{ $commentAction }}" class="flex gap-1.5">
                                            @csrf
                                            <input type="text"
                                                   name="body"
                                                   required
                                                   placeholder="Add comment..."
                                                   class="flex-1 rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-2.5 py-1 text-xs text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-lg bg-[#142B4A] hover:bg-[#0F223B] text-white text-xs font-semibold">
                                                Post
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-xs text-slate-400">
                                No tasks in this stage.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Revision Modal --}}
        <div x-show="revisionModalOpen" x-cloak style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="revisionModalOpen = false"
                 class="w-full max-w-md bg-white border border-[#DCE3EA] rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 bg-[#142B4A] text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Request Revision</h3>
                        <p class="text-xs text-sky-200" x-text="activeTaskTitle"></p>
                    </div>
                    <button type="button" @click="revisionModalOpen = false" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
                </div>
                <form method="POST" :action="revisionUrl" enctype="multipart/form-data" class="p-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Revision Reason (Mandatory)</label>
                        <textarea name="reason" rows="3" required minlength="3"
                                  placeholder="Explain QC issue or missing item..."
                                  class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Evidence Attachment (Optional)</label>
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

        {{-- Reassign Modal --}}
        <div x-show="reassignModalOpen" x-cloak style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div @click.away="reassignModalOpen = false"
                 class="w-full max-w-md bg-white border border-[#DCE3EA] rounded-2xl shadow-xl overflow-hidden">
                <div class="px-5 py-4 bg-[#142B4A] text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold">Reassign Task</h3>
                        <p class="text-xs text-sky-200" x-text="activeTaskTitle"></p>
                    </div>
                    <button type="button" @click="reassignModalOpen = false" class="text-white/80 hover:text-white text-lg font-bold">&times;</button>
                </div>
                <form method="POST" :action="reassignUrl" class="p-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">New Assignee</label>
                        <select name="assigned_to" required
                                class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]">
                            <option value="">-- Select Staff --</option>
                            @foreach ($users as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#142B4A] mb-1">Reassignment Reason (Mandatory)</label>
                        <input type="text" name="reason" required minlength="3"
                               placeholder="e.g. Shift handover / workload balancing"
                               class="w-full rounded-lg border border-[#DCE3EA] bg-[#F8FAFC] px-3 py-2 text-sm text-slate-800 focus:border-[#2563EB] focus:ring-[#2563EB]" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-[#DCE3EA]">
                        <button type="button" @click="reassignModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-[#DCE3EA] text-sm font-medium text-slate-600 hover:bg-[#F8FAFC]">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm font-semibold">
                            Confirm Reassign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
