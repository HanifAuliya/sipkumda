{{-- Satu baris data di halaman profil. --}}
@props(['icon', 'label'])

<div class="flex items-start gap-3.5 py-3.5 first:pt-0 last:pb-0">
    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-50 text-lg text-primary-500 dark:bg-primary-500/15 dark:text-primary-300">
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
        <div class="mt-0.5 text-sm font-semibold break-words text-navy-900 dark:text-white">{{ $slot }}</div>
    </div>
</div>
