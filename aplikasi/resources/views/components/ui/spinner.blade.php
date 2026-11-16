{{-- Spinner kecil. Ukuran diatur lewat class, mis. <x-ui.spinner class="size-5" /> --}}
<svg {{ $attributes->class('inline-block animate-spin') }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-90" d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
</svg>
