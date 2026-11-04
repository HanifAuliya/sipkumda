{{-- Input password dengan tombol tampilkan/sembunyikan. --}}
@props(['icon' => 'lock'])

<div x-data="{ show: false }" {{ $attributes->only('class')->class('relative') }}>
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
        <i class="bi bi-{{ $icon }}"></i>
    </span>
    <input x-bind:type="show ? 'text' : 'password'" type="password"
        {{ $attributes->except('class')->class(['form-control pr-11 pl-10', 'form-control-invalid' => $errors->has($attributes->get('name'))]) }}>
    <button type="button" x-on:click="show = !show" tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
        x-bind:aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
        <i class="bi" x-bind:class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
    </button>
</div>
