{{--
    Modal berbasis Alpine.

    <x-ui.modal id="detailModal" title="Detail Rancangan" size="4xl" on-close="resetDetail">
        isi...
        <x-slot:footer> tombol... </x-slot:footer>
    </x-ui.modal>

    Buka/tutup:
    - Blade : x-on:click="openModal('detailModal')"
    - PHP   : $this->dispatch('openModal', 'detailModal') / $this->dispatch('closeModal', 'detailModal')

    - size    : sm | md | lg | xl | 2xl | 3xl | 4xl | 5xl | 6xl
    - static  : true = tidak tertutup saat klik backdrop / Esc (mis. form panjang)
    - onClose : nama method Livewire yang dipanggil saat modal ditutup
--}}
@props([
    'id',
    'title' => null,
    'description' => null,
    'size' => '2xl',
    'static' => false,
    'onClose' => null,
])

@php
    $sizes = [
        'sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl', '3xl' => 'sm:max-w-3xl', '4xl' => 'sm:max-w-4xl', '5xl' => 'sm:max-w-5xl',
        '6xl' => 'sm:max-w-6xl',
    ];
@endphp

<div x-data="{
        open: false,
        close() {
            this.open = false;
            @if ($onClose) $wire.{{ $onClose }}(); @endif
        },
    }"
    x-on:modal-open.window="if ($event.detail === @js($id)) open = true"
    x-on:modal-close.window="if ($event.detail === @js($id)) open = false"
    @unless ($static) x-on:keydown.escape.window="open && close()" @endunless
    x-show="open" x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog" aria-modal="true" @if ($title) aria-labelledby="{{ $id }}-title" @endif
    wire:ignore.self
    {{ $attributes }}>

    <div x-show="open" x-transition.opacity.duration.200ms
        class="fixed inset-0 bg-navy-950/50 backdrop-blur-[2px]"
        @unless ($static) x-on:click="close()" @endunless></div>

    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
        <div x-show="open"
            x-trap.noscroll.inert="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            @class([
                'relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl',
                'dark:bg-navy-900 dark:ring-1 dark:ring-white/10',
                $sizes[$size] ?? $sizes['2xl'],
            ])>

            @if ($title || isset($header))
                <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-navy-800">
                    <div class="min-w-0">
                        @isset($header)
                            {{ $header }}
                        @else
                            <h3 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h3>
                            @if ($description)
                                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
                            @endif
                        @endisset
                    </div>
                    <button type="button" x-on:click="close()"
                        class="-m-1.5 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-navy-800 dark:hover:text-slate-200"
                        aria-label="Tutup">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </header>
            @endif

            <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                {{ $slot }}
            </div>

            @isset($footer)
                <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3.5 sm:px-6 dark:border-navy-800 dark:bg-navy-950/40">
                    {{ $footer }}
                </footer>
            @endisset
        </div>
    </div>
</div>
