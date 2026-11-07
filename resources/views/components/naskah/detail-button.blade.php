{{--
    Tombol pembuka modal detail naskah (livewire:v2.detail-naskah di layout).
    <x-naskah.detail-button :naskah="$naskah" />                → tombol ikon
    <x-naskah.detail-button :naskah="$naskah" label="Detail" /> → tombol berlabel
--}}
@props(['naskah', 'label' => null])

<x-ui.button :variant="$label ? 'outline-primary' : 'soft-primary'" :size="$label ? 'rapat' : 'sm'" icon="eye" :square="! $label" title="Lihat detail" aria-label="Lihat detail {{ $naskah->nomor }}"
    x-on:click="Livewire.dispatch('bukaDetailNaskah', { slug: {{ \Illuminate\Support\Js::from($naskah->slug) }} })"
    {{ $attributes }}>{{ $label }}</x-ui.button>
