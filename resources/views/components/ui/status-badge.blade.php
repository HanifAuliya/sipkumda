{{--
    Badge untuk nilai status/jenis; warnanya diambil dari App\Support\Status.
    <x-ui.status-badge :value="$rancangan->status_berkas" />

    Jenis produk hukum tampil sebagai titik warna dan namanya saja (pola "status light"): biru untuk
    Peraturan Bupati, jingga untuk Keputusan Bupati. Tanpa pil, supaya baris tabel tidak ramai;
    pil hanya dipakai untuk tahap atau status.
--}}
@props(['value' => null, 'empty' => 'N/A'])

@php
    $label = filled($value) ? \App\Support\Status::label($value) : null;
    $titik = \App\Support\Status::titikJenis($label);
@endphp

@if ($titik)
    <span {{ $attributes->class('inline-flex items-center gap-2 text-sm font-medium whitespace-nowrap text-slate-600 dark:text-slate-300') }}>
        <span class="size-2 shrink-0 rounded-full {{ $titik }}" aria-hidden="true"></span>
        {{ $label }}
    </span>
@elseif (filled($value))
    <x-ui.badge :color="\App\Support\Status::color($value)" {{ $attributes }}>{{ $label }}</x-ui.badge>
@else
    <x-ui.badge color="slate" {{ $attributes }}>{{ $empty }}</x-ui.badge>
@endif
