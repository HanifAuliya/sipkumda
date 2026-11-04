{{-- Judul halaman autentikasi. --}}
@props(['title', 'subtitle' => null])

<div class="mb-8 text-center">
    <h2 class="text-2xl font-bold">{{ $title }}</h2>
    @if ($subtitle)
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
    @endif
</div>
