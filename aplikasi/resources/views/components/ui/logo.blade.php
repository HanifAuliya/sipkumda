{{--
    Logo SIFKUMDA yang mengikuti tema: bagian abu-abu tuanya tenggelam di latar gelap,
    jadi tema gelap memakai versi terang. <x-ui.logo class="size-9" alt="Logo SIFKUMDA" />
--}}
@props(['alt' => ''])

<img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="{{ $alt }}" {{ $attributes->class('dark:hidden') }}>
<img src="{{ asset('assets/img/brand/loading_screen_terang.png') }}" alt="{{ $alt }}" {{ $attributes->class('hidden dark:block') }}>
