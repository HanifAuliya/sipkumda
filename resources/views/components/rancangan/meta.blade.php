@props(['icon'])

<li {{ $attributes->class('flex items-center gap-1.5') }}>
    <i class="bi bi-{{ $icon }} text-slate-400"></i>
    <span>{{ $slot }}</span>
</li>
