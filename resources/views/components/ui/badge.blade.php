{{--
    Badge berwarna lembut.
    <x-ui.badge color="success" icon="check2">Disetujui</x-ui.badge>
    color: primary | success | danger | warning | info | amber | navy | slate
    size : sm | lg
--}}
@props(['color' => 'slate', 'icon' => null, 'size' => 'sm'])

@php
    $colors = [
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/15 dark:bg-primary-500/15 dark:text-primary-300 dark:ring-primary-400/20',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-400/20',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-600/15 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-400/20',
        'warning' => 'bg-orange-50 text-orange-700 ring-orange-600/15 dark:bg-orange-500/15 dark:text-orange-300 dark:ring-orange-400/20',
        'info' => 'bg-cyan-50 text-cyan-700 ring-cyan-600/15 dark:bg-cyan-500/15 dark:text-cyan-300 dark:ring-cyan-400/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-400/20',
        'navy' => 'bg-navy-900 text-white ring-navy-900 dark:bg-navy-700 dark:ring-navy-600',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/15 dark:bg-white/10 dark:text-slate-300 dark:ring-white/10',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full font-semibold ring-1 ring-inset whitespace-nowrap', $size === 'lg' ? 'px-3 py-1 text-sm' : 'px-2.5 py-0.5 text-xs', $colors[$color] ?? $colors['slate']]) }}>
    @if ($icon)
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
