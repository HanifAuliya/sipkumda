{{--
    Link ke file privat (dibuka lewat route /view-private/...).
    <x-ui.file-link folder="rancangan/matrik" :path="$rancangan->matrik" label="Lihat Matrik" />
    Atau langsung dengan URL: <x-ui.file-link :href="$url" label="..." />
--}}
@props(['folder' => null, 'path' => null, 'href' => null, 'label' => 'Lihat File', 'empty' => 'Data tidak ada'])

@php
    $url = $href ?? ($folder ? private_file_url($folder, $path) : null);
    $ext = strtolower(pathinfo((string) ($path ?? $href), PATHINFO_EXTENSION));
    [$icon, $color] = match ($ext) {
        'pdf' => ['file-earmark-pdf-fill', 'text-rose-500'],
        'doc', 'docx' => ['file-earmark-word-fill', 'text-primary-500'],
        'xls', 'xlsx' => ['file-earmark-excel-fill', 'text-emerald-500'],
        'png', 'jpg', 'jpeg' => ['file-earmark-image-fill', 'text-amber-500'],
        default => ['file-earmark-text-fill', 'text-slate-400'],
    };
@endphp

@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener"
        {{ $attributes->class('group inline-flex items-center gap-2 font-medium text-primary-600 hover:text-primary-700 dark:text-primary-300') }}>
        <i class="bi bi-{{ $icon }} {{ $color }} text-base" aria-hidden="true"></i>
        <span class="underline-offset-2 group-hover:underline">{{ $label }}</span>
        <i class="bi bi-box-arrow-up-right text-[0.65rem] opacity-0 transition group-hover:opacity-70" aria-hidden="true"></i>
    </a>
@else
    <span {{ $attributes->class('text-sm text-slate-400 italic dark:text-slate-500') }}>{{ $empty }}</span>
@endif
