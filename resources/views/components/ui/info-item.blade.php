@props(['label'])

<div {{ $attributes->class('grid gap-1 py-2.5 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:gap-4') }}>
    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</dt>
    <dd class="min-w-0 text-sm break-words text-slate-800 dark:text-slate-200">{{ $slot }}</dd>
</div>
