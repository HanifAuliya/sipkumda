{{-- Paginasi berbasis link (GET) untuk halaman publik: $hasil->links('jdih.paginasi') --}}
@if ($paginator->hasPages())
    @php
        $item = 'grid min-w-10 place-items-center rounded-lg px-3 py-2 text-sm font-semibold transition';
        $biasa = $item . ' text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 dark:text-slate-300 dark:ring-navy-700 dark:hover:bg-navy-800';
        $mati = $item . ' text-slate-300 ring-1 ring-slate-100 dark:text-navy-700 dark:ring-navy-800';
    @endphp

    <nav class="flex flex-col items-center justify-between gap-4 sm:flex-row" aria-label="Navigasi halaman">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Menampilkan <strong class="text-navy-900 dark:text-white">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
            dari <strong class="text-navy-900 dark:text-white">{{ number_format($paginator->total(), 0, ',', '.') }}</strong> hasil
        </p>

        <ul class="flex flex-wrap items-center gap-1.5">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $mati }}" aria-disabled="true"><i class="bi bi-chevron-left"></i><span class="sr-only">Sebelumnya</span></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $biasa }}"><i class="bi bi-chevron-left"></i><span class="sr-only">Sebelumnya</span></a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-1 text-slate-400">{{ $element }}</span></li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $item }} bg-navy-900 text-white dark:bg-primary-500">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $biasa }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $biasa }}"><i class="bi bi-chevron-right"></i><span class="sr-only">Berikutnya</span></a>
                @else
                    <span class="{{ $mati }}" aria-disabled="true"><i class="bi bi-chevron-right"></i><span class="sr-only">Berikutnya</span></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
