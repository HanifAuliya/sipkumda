{{--
    Badge untuk nilai status/jenis; warnanya diambil dari App\Support\Status.
    <x-ui.status-badge :value="$rancangan->status_berkas" />

    Jenis produk hukum diberi kode berwarna pekat di depan namanya (PB biru, SK jingga),
    supaya Peraturan Bupati dan Keputusan Bupati langsung terbedakan di daftar dan tabel.
--}}
@props(['value' => null, 'empty' => 'N/A'])

@php
    $label = filled($value) ? \App\Support\Status::label($value) : null;
    $kode = \App\Support\Status::KODE_JENIS[$label] ?? null;
@endphp

@if ($kode)
    <x-ui.badge :color="\App\Support\Status::color($value)" {{ $attributes->class('pl-1!') }}>
        <span @class([
            'rounded-full px-1.5 py-px text-sm leading-tight font-bold text-white',
            'bg-primary-500' => $kode === 'PB',
            'bg-accent-500' => $kode === 'SK',
        ]) aria-hidden="true">{{ $kode }}</span>
        {{ $label }}
    </x-ui.badge>
@elseif (filled($value))
    <x-ui.badge :color="\App\Support\Status::color($value)" {{ $attributes }}>{{ $label }}</x-ui.badge>
@else
    <x-ui.badge color="slate" {{ $attributes }}>{{ $empty }}</x-ui.badge>
@endif
