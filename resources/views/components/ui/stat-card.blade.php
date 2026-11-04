{{--
    Kartu statistik.
    <x-ui.stat-card label="Rancangan" :value="12" suffix="Data" icon="clipboard2-data" color="rose" />
    color: rose | orange | emerald | sky | primary | amber
--}}
@props(['label', 'value' => 0, 'suffix' => null, 'icon' => 'bar-chart', 'color' => 'primary', 'href' => null])

@php
    $gradients = [
        'rose' => 'from-rose-500 to-pink-500',
        'orange' => 'from-orange-500 to-amber-400',
        'emerald' => 'from-emerald-500 to-teal-400',
        'sky' => 'from-sky-500 to-cyan-400',
        'primary' => 'from-primary-500 to-violet-500',
        'amber' => 'from-amber-500 to-yellow-400',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class('group flex items-start justify-between gap-4 rounded-2xl bg-white p-5 shadow-card transition dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5' . ($href ? ' hover:-translate-y-0.5 hover:shadow-lg' : '')) }}>
    <div class="min-w-0">
        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ $label }}</p>
        <p class="mt-2 text-2xl font-bold text-navy-900 dark:text-white">
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
    <span class="grid size-12 shrink-0 place-items-center rounded-full bg-linear-to-br {{ $gradients[$color] ?? $gradients['primary'] }} text-xl text-white shadow-soft">
        <i class="bi bi-{{ $icon }}"></i>
    </span>
</{{ $tag }}>
