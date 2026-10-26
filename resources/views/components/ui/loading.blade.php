{{--
    Indikator loading Livewire.
    <x-ui.loading target="search">Mencari...</x-ui.loading>
--}}
@props(['target' => null])

<div wire:loading.flex @if ($target) wire:target="{{ $target }}" @endif
    {{ $attributes->class('items-center gap-2 text-xs font-medium text-primary-600 dark:text-primary-300') }}>
    <x-ui.spinner class="size-3.5" />
    <span>{{ $slot->isEmpty() ? 'Memuat...' : $slot }}</span>
</div>
