{{--
    Tombol tab. Logika aktif diatur oleh pemanggil (Livewire / Alpine).
    <x-ui.tab :active="$tab === 'menunggu'" icon="hourglass" wire:click="$set('tab', 'menunggu')">Menunggu</x-ui.tab>
--}}
@props(['active' => false, 'icon' => null, 'count' => null])

<button type="button" role="tab" aria-selected="{{ $active ? 'true' : 'false' }}"
    {{ $attributes->class([
        'inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition',
        'bg-primary-500 text-white shadow-soft' => $active,
        'text-slate-600 hover:bg-white hover:text-primary-600 dark:text-slate-300 dark:hover:bg-navy-800' => ! $active,
    ]) }}>
    @if ($icon)<i class="bi bi-{{ $icon }}"></i>@endif
    {{ $slot }}
    @if (! is_null($count))
        <span @class([
            'rounded-full px-2 py-0.5 text-[0.7rem] leading-none',
            'bg-white/20 text-white' => $active,
            'bg-slate-200 text-slate-600 dark:bg-navy-700 dark:text-slate-300' => ! $active,
        ])>{{ $count }}</span>
    @endif
</button>
