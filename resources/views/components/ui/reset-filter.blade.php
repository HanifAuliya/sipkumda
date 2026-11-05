{{--
    Tombol mengosongkan pencarian dan filter, hanya tampil bila ada yang aktif.
    <x-ui.reset-filter :aktif="filled($search)" :nilai="['search' => '', 'tahap' => '']" />
--}}
@props(['aktif' => false, 'nilai' => []])

@if ($aktif)
    <x-ui.button variant="outline" size="rapat" icon="x-circle" {{ $attributes }}
        x-on:click="Object.entries({{ \Illuminate\Support\Js::from($nilai) }}).forEach(([nama, isi]) => $wire.$set(nama, isi))">
        Hapus pencarian dan filter
    </x-ui.button>
@endif
