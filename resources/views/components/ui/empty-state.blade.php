{{-- Tampilan kosong. <x-ui.empty-state icon="inbox" title="Belum ada data" description="..." /> --}}
@props(['icon' => 'inbox', 'title' => 'Tidak ada data.', 'description' => null])

<div {{ $attributes->class('flex flex-col items-center justify-center gap-2 py-6 text-center') }}>
    <span class="grid size-14 place-items-center rounded-full bg-slate-100 text-2xl text-slate-400 dark:bg-navy-800 dark:text-slate-500">
        <i class="bi bi-{{ $icon }}"></i>
    </span>
    <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
