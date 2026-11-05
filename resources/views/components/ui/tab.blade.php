{{--
    Tombol tab. Logika aktif diatur oleh pemanggil (Livewire / Alpine).
    <x-ui.tab :active="$tab === 'menunggu'" icon="hourglass" wire:click="$set('tab', 'menunggu')">Menunggu</x-ui.tab>
--}}
@props(['active' => false, 'icon' => null, 'count' => null])

{{-- Warna mengikuti aria-selected, jadi tab yang diklik bisa langsung tampak aktif (resources/js/navigasi.js). --}}
<button type="button" role="tab" aria-selected="{{ $active ? 'true' : 'false' }}"
    {{ $attributes->class([
        'group inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-center text-sm font-semibold transition duration-150 ease-out active:scale-[0.98] sm:whitespace-nowrap',
        'text-slate-600 hover:bg-white hover:text-primary-600 dark:text-slate-300 dark:hover:bg-navy-800',
        'aria-selected:bg-primary-500 aria-selected:text-white aria-selected:shadow-soft aria-selected:hover:bg-primary-500 aria-selected:hover:text-white dark:aria-selected:hover:bg-primary-500',
    ]) }}>
    @if ($icon)<i class="bi bi-{{ $icon }} max-sm:hidden" aria-hidden="true"></i>@endif
    {{ $slot }}
    @if (! is_null($count))
        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs leading-none text-slate-600 group-aria-selected:bg-white/20 group-aria-selected:text-white dark:bg-navy-700 dark:text-slate-300">{{ $count }}</span>
    @endif
</button>
