{{--
    Select. <x-form.select wire:model.live="tahun" icon="calendar3" class="md:col-span-3"> <option>..</option> </x-form.select>
    Atribut "class" diterapkan ke pembungkus (untuk tata letak grid), sisanya ke <select>.
--}}
@props(['icon' => null, 'error' => null])

@php
    $field = $error ?? $attributes->wire('model')->value() ?: $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp

<div {{ $attributes->only('class')->class('relative') }}>
    @if ($icon)
        <span class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3.5 text-slate-400">
            <i class="bi bi-{{ $icon }}"></i>
        </span>
    @endif
    <select {{ $attributes->except('class')->class(['form-control pr-10', 'pl-10' => $icon, 'form-control-invalid' => $invalid]) }}>
        {{ $slot }}
    </select>
</div>
