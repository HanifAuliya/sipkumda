{{--
    Sidebar: laci di mobile, bisa diciutkan menjadi ikon di desktop. Menu diatur di App\Support\Navigation.
    Keadaan ciut diatur CSS lewat atribut data-menu-ciut pada <html>, supaya tidak berkedip saat berpindah halaman.
--}}
@php
    $sections = \App\Support\Navigation::for(auth()->user());
    // v2 berpindah menu tanpa memuat ulang halaman; aplikasi lama tetap seperti semula.
    $tanpaMuatUlang = \App\Support\Navigation::modeV2();
@endphp

{{-- Latar gelap saat laci terbuka (mobile) --}}
<div x-show="sidebarOpen" x-cloak x-transition.opacity x-on:click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-navy-950/50 backdrop-blur-[2px] lg:hidden"></div>

<aside data-menu-utama
    class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-white shadow-xl transition-transform duration-200 lg:translate-x-0 lg:shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5"
    x-bind:class="sidebarOpen && 'translate-x-0'"
    aria-label="Navigasi utama">

    {{-- Logo --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-5 dark:border-navy-800" data-ciut-tengah>
        <a href="{{ \App\Support\Navigation::beranda() }}" class="flex min-w-0 items-center gap-3" @if ($tanpaMuatUlang) wire:navigate @endif>
            <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="Logo SIFKUMDA" class="size-9 shrink-0">
            <span class="truncate text-lg font-extrabold tracking-tight text-navy-900 dark:text-white" data-ciut-sembunyi>
                SIFKUMDA <span class="text-amber-500">HST</span>
            </span>
        </a>
        <button type="button" class="ml-auto grid size-10 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 lg:hidden dark:hover:bg-navy-800 dark:hover:text-slate-200"
            x-on:click="sidebarOpen = false" aria-label="Tutup menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Menu. Posisi gulir dan penanda aktifnya diatur resources/js/navigasi.js. --}}
    <nav class="tanpa-scrollbar menu-pudar relative flex-1 space-y-4 overflow-y-auto px-3 pt-4 pb-6" data-menu-samping
        x-init="$el.querySelector('[aria-current=page]')?.scrollIntoView({ block: 'nearest' })"
        x-on:click="$event.target.closest('a') && (sidebarOpen = false)">
        @if ($tanpaMuatUlang)
            <span class="penanda-menu" data-penanda-menu aria-hidden="true"></span>
        @endif

        @foreach ($sections as $section)
            <div>
                @if ($section['title'])
                    <p class="mb-1 px-3 text-sm font-semibold text-slate-500 dark:text-slate-400" data-ciut-sembunyi>
                        {{ $section['title'] }}
                    </p>
                    <hr class="mx-3 mb-2 hidden border-slate-100 dark:border-navy-800" data-ciut-tampil>
                @endif

                <ul class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        <li>
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}" title="{{ $item['label'] }}" @if ($tanpaMuatUlang) wire:navigate @endif
                                class="menu-item" data-ciut-tengah
                                @if (\App\Support\Navigation::aktif($item)) aria-current="page" @endif>
                                <i class="bi bi-{{ $item['icon'] }}" aria-hidden="true"></i>
                                <span class="truncate" data-ciut-sembunyi>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Ciutkan (desktop) --}}
    <div class="hidden border-t border-slate-100 p-3 lg:block dark:border-navy-800">
        <button type="button" x-on:click="sidebarCollapsed = !sidebarCollapsed" data-ciut-tengah
            x-bind:aria-label="sidebarCollapsed ? 'Lebarkan menu' : 'Ciutkan menu'"
            class="flex min-h-10 w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-navy-800">
            <i class="bi text-lg" x-bind:class="sidebarCollapsed ? 'bi-chevron-double-right' : 'bi-chevron-double-left'" aria-hidden="true"></i>
            <span data-ciut-sembunyi>Ciutkan menu</span>
        </button>
    </div>
</aside>
