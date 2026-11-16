{{--
    Tombol ganti tema. Warnanya mengikuti warna teks pembungkusnya, jadi cocok untuk topbar navy maupun header putih.
    Bulan tampil pada tema terang dan matahari pada tema gelap; perpindahannya diatur CSS lewat class "dark".
--}}
<button type="button" x-on:click="toggleTheme()" title="Ganti tema"
    {{ $attributes->class('tombol-tema grid size-10 shrink-0 place-items-center rounded-lg text-current/80 hover:bg-current/10 hover:text-current') }}>
    <span class="sr-only dark:hidden">Aktifkan tema gelap</span>
    <span class="sr-only hidden dark:inline">Aktifkan tema terang</span>
    <svg class="ikon-tema" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path class="ikon-bulan" d="M20.7 14.6a8.6 8.6 0 0 1-11.3-11.3.75.75 0 0 0-1-.95A10 10 0 1 0 21.65 15.6a.75.75 0 0 0-.95-1Z" />
        <g class="ikon-matahari" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="4.25" stroke="none" />
            <path fill="none" d="M12 2.75v1.5M12 19.75v1.5M2.75 12h1.5M19.75 12h1.5M5.46 5.46l1.06 1.06M17.48 17.48l1.06 1.06M5.46 18.54l1.06-1.06M17.48 6.52l1.06-1.06" />
        </g>
    </svg>
</button>
