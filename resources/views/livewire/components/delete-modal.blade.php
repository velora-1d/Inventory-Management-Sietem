<div
    x-data="{ show: @entangle('open') }"
    x-show="show"
    x-on:keydown.escape.window="show = false"
    class="relative z-[200]"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    style="display: none;"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[199] backdrop-blur-sm bg-black/40"
    ></div>

    <div class="fixed inset-0 z-[200] w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Modal Panel -->
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                @click.away="show = false"
            >
                <!-- Top accent bar -->
                <div class="h-1 w-full bg-gradient-to-r from-red-500 via-rose-500 to-red-600"></div>

                <div class="p-6">
                    <!-- Icon + Title -->
                    <div class="flex items-start gap-4">
                        <!-- Danger Icon -->
                        <div class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-red-50 border-2 border-red-100">
                            <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <!-- Content -->
                        <div class="flex-1 pt-0.5">
                            <h3 class="text-base font-semibold text-gray-900" id="modal-title">
                                {{ $title }}
                            </h3>
                            <p class="mt-1.5 text-sm text-gray-500 leading-relaxed">
                                {{ $description }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                        <!-- Cancel -->
                        <button
                            type="button"
                            @click="show = false"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Batal
                        </button>
                        <!-- Confirm -->
                        <button
                            type="button"
                            wire:click="confirm"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-sm shadow-red-200 active:scale-[0.98] transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <!-- Loading Spinner -->
                            <svg wire:loading wire:target="confirm" class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <!-- Trash Icon (hidden while loading) -->
                            <svg wire:loading.remove wire:target="confirm" class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            {{ $confirmButtonText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
