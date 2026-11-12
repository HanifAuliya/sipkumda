{{--
    Bola hiasan yang melayang pelan di belakang isi kartu. Induknya perlu "relative overflow-hidden",
    dan isinya "relative" supaya tetap di depan.
    <x-ui.bola />              di atas latar biru tua (sambutan Dashboard, kartu Profil)
    <x-ui.bola nada="terang" /> di atas kartu putih (kepala halaman)
    Gerakannya berhenti bila pengguna mematikan animasi di perangkatnya.
--}}
@props(['nada' => 'gelap'])

@php
    $warna = $nada === 'terang'
        ? ['bg-primary-500/[0.06] dark:bg-primary-400/10', 'bg-sky-400/[0.08] dark:bg-sky-400/10', 'bg-primary-400/[0.07] dark:bg-primary-300/10']
        : ['bg-white/[0.06]', 'bg-primary-400/15', 'bg-sky-300/10'];
@endphp

<div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden rounded-[inherit]">
    @if ($nada === 'terang')
        <div class="bola-melayang-a absolute -top-20 right-24 size-48 rounded-full {{ $warna[0] }}"></div>
        <div class="bola-melayang-b absolute -right-16 -bottom-24 size-56 rounded-full {{ $warna[1] }}"></div>
        <div class="bola-melayang-c absolute top-4 right-1/3 size-10 rounded-full {{ $warna[2] }} max-sm:hidden"></div>
    @else
        <div class="bola-melayang-a absolute -top-16 -right-10 size-64 rounded-full {{ $warna[0] }}"></div>
        <div class="bola-melayang-b absolute -right-24 -bottom-24 size-72 rounded-full {{ $warna[1] }}"></div>
        <div class="bola-melayang-c absolute top-6 right-1/3 size-16 rounded-full {{ $warna[2] }}"></div>
        <div class="bola-melayang-a absolute bottom-3 left-1/2 size-6 rounded-full bg-white/10 [animation-delay:-7s] max-sm:hidden"></div>
    @endif
</div>
