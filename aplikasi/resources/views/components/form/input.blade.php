{{--
    Input teks. Status error otomatis dari nama wire:model / name.
    <x-form.input wire:model.live="search" icon="search" placeholder="Cari..." class="md:col-span-4" />
    Atribut "class" diterapkan ke pembungkus (untuk tata letak grid), sisanya ke <input>.
--}}
@props(['icon' => null, 'error' => null])

@php
    $field = $error ?? $attributes->wire('model')->value() ?: $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp

<div {{ $attributes->only('class')->class('relative') }}>
    @if ($icon)
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
            <i class="bi bi-{{ $icon }}"></i>
        </span>
    @endif
    <input {{ $attributes->except('class')->merge(['type' => 'text'])->class(['form-control', 'pl-10' => $icon, 'form-control-invalid' => $invalid]) }}>
</div>
