{{-- Pagination Livewire bergaya aplikasi. Dipakai otomatis oleh ->links() (config livewire.pagination_theme = tailwind). --}}
@php
    $scrollTo = $scrollTo ?? 'body';
    $scroll = $scrollTo !== false
        ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView({ behavior: 'smooth' })"
        : '';
    $pageName = $paginator->getPageName();
    $btn = 'grid size-9 place-items-center rounded-lg text-sm font-semibold transition';
    $idle = 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-navy-800';
    $disabled = 'cursor-not-allowed text-slate-300 dark:text-navy-700';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Menampilkan
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-slate-700 dark:text-slate-200">{{ $paginator->lastItem() }}</span>
                dari <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</span> data
            </p>

            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="{{ $btn }} {{ $disabled }}" aria-disabled="true"><i class="bi bi-chevron-left"></i></span>
                @else
                    <button type="button" wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scroll }}"
                        wire:loading.attr="disabled" class="{{ $btn }} {{ $idle }}" aria-label="Sebelumnya">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $btn }} text-slate-400" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $pageName }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span class="{{ $btn }} bg-navy-900 text-white shadow-soft dark:bg-primary-500" aria-current="page">{{ $page }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" x-on:click="{{ $scroll }}"
                                        class="{{ $btn }} {{ $idle }}" aria-label="Halaman {{ $page }}">{{ $page }}</button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scroll }}"
                        wire:loading.attr="disabled" class="{{ $btn }} {{ $idle }}" aria-label="Berikutnya">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                @else
                    <span class="{{ $btn }} {{ $disabled }}" aria-disabled="true"><i class="bi bi-chevron-right"></i></span>
                @endif
            </div>
        </nav>
    @endif
</div>
