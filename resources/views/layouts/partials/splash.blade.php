{{-- Layar pembuka: hanya sekali per sesi browser, tidak di setiap pindah halaman. --}}
<div x-data="{
        show: (() => { try { return !sessionStorage.getItem('splash-shown'); } catch (e) { return false; } })(),
        init() {
            if (!this.show) return;
            try { sessionStorage.setItem('splash-shown', '1'); } catch (e) {}
            setTimeout(() => this.show = false, 1100);
        },
    }"
    x-show="show" x-cloak
    x-transition:leave="transition duration-500 ease-in"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[100] grid place-items-center bg-white dark:bg-navy-950">
    <div class="flex flex-col items-center gap-3 text-center">
        <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="" class="size-28 animate-pulse">
        <h1 class="text-3xl font-extrabold tracking-tight text-amber-500">SIFKUMDA HST</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Sistem Fasilitasi Produk Hukum Daerah</p>
    </div>
</div>
