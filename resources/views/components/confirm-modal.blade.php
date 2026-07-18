{{--
    Reusable Aesthetic Confirmation Modal
    Usage:
      <x-confirm-modal name="my-modal" title="..." message="..." btnText="..." btnClass="...">
         <!-- optional: slot for extra form content -->
      </x-confirm-modal>
--}}
@props([
    'name',
    'title'    => 'Konfirmasi Aksi',
    'message'  => 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    'btnText'  => 'Konfirmasi',
    'btnClass' => 'from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 focus:ring-red-400 shadow-red-200',
])

<div
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail.name === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail.name === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-[200]"
    style="display: none;"
    role="dialog"
    aria-modal="true"
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
        class="fixed inset-0 backdrop-blur-sm bg-black/40"
    ></div>

    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
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
                <!-- Top accent bar - dynamic per button class -->
                <div class="h-1 w-full bg-gradient-to-r {{ $btnClass }}"></div>

                <div class="p-6">
                    <!-- Icon + Header -->
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-amber-50 border-2 border-amber-100">
                            <svg class="w-6 h-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <div class="flex-1 pt-0.5">
                            <h3 class="text-base font-semibold text-gray-900" x-text="modalTitle || '{{ $title }}'">{{ $title }}</h3>
                            <p class="mt-1.5 text-sm text-gray-500 leading-relaxed" x-text="modalMessage || '{{ $message }}'">{{ $message }}</p>
                        </div>
                    </div>

                    <!-- Slot for extra content (e.g. form fields) -->
                    @if ($slot->isNotEmpty())
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            {{ $slot }}
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5" x-data="{ submitting: false }">
                        <!-- Cancel -->
                        <button
                            type="button"
                            @click="show = false; $dispatch('close-modal', { name: '{{ $name }}' })"
                            x-bind:disabled="submitting"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50"
                        >
                            <svg class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Batal
                        </button>

                        <!-- Confirm (form submit) -->
                        <form :action="actionUrl" method="POST" @submit="submitting = true" id="confirm-form-{{ $name }}">
                            @csrf
                            <input type="hidden" name="_method" :value="actionMethod">
                            <button
                                type="submit"
                                x-bind:disabled="submitting"
                                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r {{ $btnClass }} shadow-sm active:scale-[0.98] transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-60 disabled:cursor-not-allowed"
                                x-bind:class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                            >
                                <svg x-show="submitting" class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg x-show="!submitting" class="w-4 h-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span x-text="confirmButtonText || '{{ $btnText }}'">{{ $btnText }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
