{{--
    Kartu statistik.
    <x-ui.stat-card label="Rancangan" :value="12" suffix="Data" icon="clipboard2-data" color="rose" />
    color: dipertahankan untuk pemanggil lama; tampilannya kini netral
    rapat: di HP kartunya ringkas dan muat dua kolom
--}}
@props(['label', 'value' => 0, 'suffix' => null, 'icon' => 'bar-chart', 'color' => 'primary', 'href' => null, 'rapat' => false])

@php
    // Satu gaya netral untuk semua kartu: angkanya yang dibaca, ikon hanya penanda. "color" tetap diterima supaya pemanggil lama tidak rusak.
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class('group flex items-start justify-between gap-4 rounded-2xl bg-white p-5 shadow-card transition dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5' . ($href ? ' hover:bg-slate-50 dark:hover:bg-navy-800' : '') . ($rapat ? ' max-sm:relative max-sm:p-4' : '')) }}>
    <div class="min-w-0">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
        <p class="mt-2 text-3xl font-bold tracking-tight text-navy-900 dark:text-white">
            @if (is_numeric($value))
                {{-- Angka dihitung naik saat kartu pertama kali terlihat --}}
                <span x-data="{ v: {{ (int) $value }}, to: {{ (int) $value }} }"
                    x-intersect.once="v = 0; const start = performance.now(); const tick = (t) => { const p = Math.min((t - start) / 900, 1); v = Math.round(to * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(tick); }; requestAnimationFrame(tick);"
                    x-text="v">{{ $value }}</span>
            @else
                {{ $value }}
            @endif
            @if ($suffix)<span class="text-sm font-medium text-slate-400">{{ $suffix }}</span>@endif
        </p>
        {{ $slot }}
    </div>
    <span @class([
        'grid size-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-lg text-slate-500 dark:bg-navy-800 dark:text-slate-400',
        'max-sm:absolute max-sm:right-3 max-sm:bottom-3 max-sm:size-9 max-sm:text-base' => $rapat,
    ])>
        <i class="bi bi-{{ $icon }}"></i>
    </span>
</{{ $tag }}>
