@props(['position' => 'top-center'])

<div
    x-data="{
        notifications: [
            @if(session()->has('success'))
                { id: Date.now(), message: @js(session('success')), type: 'success' },
            @endif
            @if(session()->has('error'))
                { id: Date.now() + 1, message: @js(session('error')), type: 'error' },
            @endif
            @if(session()->has('warning'))
                { id: Date.now() + 2, message: @js(session('warning')), type: 'warning' },
            @endif
            @if(session()->has('info'))
                { id: Date.now() + 3, message: @js(session('info')), type: 'info' },
            @endif
        ],
        timers: {},
        init() {
            this.notifications.forEach(n => {
                this.startTimer(n.id);
            });
        },
        startTimer(id) {
            this.timers[id] = setTimeout(() => this.remove(id), 5000);
        },
        add(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.notifications.unshift({ id, message, type, progress: 100 });
            if (this.notifications.length > 5) {
                const last = this.notifications.pop();
                clearTimeout(this.timers[last.id]);
            }
            this.startTimer(id);
        },
        remove(id) {
            clearTimeout(this.timers[id]);
            delete this.timers[id];
            this.notifications = this.notifications.filter(n => n.id !== id);
        },
        config(type) {
            const map = {
                success: {
                    bar:  'bg-emerald-500',
                    icon: 'bg-emerald-50 text-emerald-600',
                    border: 'border-emerald-100',
                    ring: 'ring-emerald-50',
                },
                error: {
                    bar:  'bg-red-500',
                    icon: 'bg-red-50 text-red-600',
                    border: 'border-red-100',
                    ring: 'ring-red-50',
                },
                warning: {
                    bar:  'bg-amber-500',
                    icon: 'bg-amber-50 text-amber-600',
                    border: 'border-amber-100',
                    ring: 'ring-amber-50',
                },
                info: {
                    bar:  'bg-sky-500',
                    icon: 'bg-sky-50 text-sky-600',
                    border: 'border-sky-100',
                    ring: 'ring-sky-50',
                },
            };
            return map[type] || map.info;
        }
    }"
    x-on:toast.window="add($event.detail.message, $event.detail.type)"
    class="fixed top-4 right-4 z-[500] flex flex-col gap-2 w-full max-w-sm pointer-events-none"
>
    <template x-for="(n, index) in notifications" :key="n.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-full scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-full scale-90"
            class="pointer-events-auto relative flex items-start gap-3 overflow-hidden rounded-xl border bg-white p-4 shadow-lg shadow-black/5 ring-1"
            :class="config(n.type).border + ' ' + config(n.type).ring"
        >
            <!-- Left accent bar -->
            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl" :class="config(n.type).bar"></div>

            <!-- Icon -->
            <div class="ml-1 flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full" :class="config(n.type).icon">

                <!-- Success icon -->
                <template x-if="n.type === 'success'">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </template>

                <!-- Error icon -->
                <template x-if="n.type === 'error'">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>

                <!-- Warning icon -->
                <template x-if="n.type === 'warning'">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </template>

                <!-- Info icon -->
                <template x-if="n.type === 'info'">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </template>
            </div>

            <!-- Message -->
            <div class="flex-1 min-w-0 pt-0.5">
                <!-- Type label -->
                <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" :class="{
                    'text-emerald-600': n.type === 'success',
                    'text-red-600':     n.type === 'error',
                    'text-amber-600':   n.type === 'warning',
                    'text-sky-600':     n.type === 'info'
                }" x-text="{
                    success: 'Berhasil',
                    error:   'Gagal',
                    warning: 'Peringatan',
                    info:    'Info'
                }[n.type] || n.type"></p>
                <p x-text="n.message" class="text-sm font-medium text-gray-800 leading-snug break-words"></p>
            </div>

            <!-- Close button -->
            <button
                @click="remove(n.id)"
                class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
            >
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Progress bar at bottom -->
            <div class="absolute bottom-0 left-0 right-0 h-0.5 opacity-30 rounded-full" :class="config(n.type).bar"
                 x-data="{ w: 100 }"
                 x-init="setTimeout(() => { w = 0 }, 50)"
                 :style="`width: ${w}%; transition: width 5s linear;`"
            ></div>
        </div>
    </template>
</div>
