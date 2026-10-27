{{-- Sidebar: laci di mobile, bisa diciutkan menjadi ikon di desktop. Menu diatur di App\Support\Navigation. --}}
@php
    $sections = \App\Support\Navigation::for(auth()->user());
    $current = request()->route()?->getName();
@endphp

{{-- Latar gelap saat laci terbuka (mobile) --}}
<div x-show="sidebarOpen" x-cloak x-transition.opacity x-on:click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-navy-950/50 backdrop-blur-[2px] lg:hidden"></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-white shadow-xl transition-[transform,width] duration-200 lg:translate-x-0 lg:shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5"
    x-bind:class="{ 'translate-x-0': sidebarOpen, 'lg:w-20': sidebarCollapsed }"
    aria-label="Navigasi utama">

    {{-- Logo --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-5 dark:border-navy-800"
        x-bind:class="sidebarCollapsed && 'lg:justify-center lg:px-0'">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="Logo SIFKUMDA" class="size-9 shrink-0">
            <span class="truncate text-lg font-extrabold tracking-tight text-navy-900 dark:text-white"
                x-bind:class="sidebarCollapsed && 'lg:hidden'">
                SIFKUMDA <span class="text-amber-500">HST</span>
            </span>
        </a>
        <button type="button" class="ml-auto rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden dark:hover:bg-navy-800"
            x-on:click="sidebarOpen = false" aria-label="Tutup menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
        @foreach ($sections as $section)
            <div>
                @if ($section['title'])
                    <p class="mb-2 px-3 text-[0.68rem] font-bold tracking-wider text-slate-400 uppercase"
                        x-bind:class="sidebarCollapsed && 'lg:hidden'">
                        {{ $section['title'] }}
                    </p>
                    <hr class="mx-3 mb-2 hidden border-slate-100 dark:border-navy-800"
                        x-bind:class="sidebarCollapsed && 'lg:block'">
                @endif

                <ul class="space-y-1">
                    @foreach ($section['items'] as $item)
                        @php $active = $current === $item['route']; @endphp
                        <li>
                            <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                                @class([
                                    'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                                    'bg-accent-500 text-white shadow-soft' => $active,
                                    'text-slate-600 hover:bg-slate-100 hover:text-navy-900 dark:text-slate-300 dark:hover:bg-navy-800 dark:hover:text-white' => ! $active,
                                ])
                                x-bind:class="sidebarCollapsed && 'lg:justify-center lg:px-0'"
                                @if ($active) aria-current="page" @endif>
                                <i @class([
                                    'bi bi-' . $item['icon'],
                                    'text-lg leading-none',
                                    'text-primary-500 group-hover:text-primary-600 dark:text-primary-300' => ! $active,
                                ])></i>
                                <span class="truncate" x-bind:class="sidebarCollapsed && 'lg:hidden'">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Ciutkan (desktop) --}}
    <div class="hidden border-t border-slate-100 p-3 lg:block dark:border-navy-800">
        <button type="button" x-on:click="sidebarCollapsed = !sidebarCollapsed"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-navy-800"
            x-bind:class="sidebarCollapsed && 'justify-center px-0'">
            <i class="bi text-lg" x-bind:class="sidebarCollapsed ? 'bi-chevron-double-right' : 'bi-chevron-double-left'"></i>
            <span x-show="!sidebarCollapsed">Ciutkan menu</span>
        </button>
    </div>
</aside>
