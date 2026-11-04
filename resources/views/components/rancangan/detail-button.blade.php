{{--
    Tombol pembuka modal detail rancangan global (livewire:notification-modal.detail-rancangan di layout).
    <x-rancangan.detail-button :rancangan="$item" />            → tombol ikon
    <x-rancangan.detail-button :rancangan="$item" label="Detail" /> → tombol berlabel
--}}
@props(['rancangan', 'label' => null, 'variant' => 'navy', 'size' => 'sm'])

<x-ui.button :variant="$variant" :size="$size" icon="eye" :square="! $label" title="Lihat detail"
    x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($rancangan->slug) }} })"
    {{ $attributes }}>{{ $label }}</x-ui.button>
