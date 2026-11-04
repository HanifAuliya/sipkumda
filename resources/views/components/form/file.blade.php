{{--
    Input file dengan indikator unggah Livewire.
    <x-form.file wire:model="rancangan" accept=".pdf" />
--}}
@props(['error' => null])

@php
    $model = $attributes->wire('model')->value();
    $field = $error ?? $model ?: $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp

<div {{ $attributes->only('class') }}>
    <input type="file" {{ $attributes->except('class')->class([
        'block w-full cursor-pointer rounded-lg border border-dashed bg-slate-50/60 p-1.5 text-sm text-slate-600',
        'file:mr-4 file:cursor-pointer file:rounded-md file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-600 hover:file:bg-primary-100',
        'focus:outline-none focus:ring-3 focus:ring-primary-100',
        'dark:bg-navy-950/40 dark:text-slate-300 dark:file:bg-primary-500/15 dark:file:text-primary-300',
        'border-rose-300 dark:border-rose-500/60' => $invalid,
        'border-slate-300 dark:border-navy-700' => ! $invalid,
    ]) }}>
    @if ($model)
        <x-ui.loading :target="$model" class="mt-1.5">Mengunggah file...</x-ui.loading>
    @endif
</div>
