{{--
    Layar saat masuk aplikasi, dengan efek ketikan seperti tampilan sebelum migrasi.
    Tampil sekali setelah login; berpindah menu tidak memunculkannya lagi.
    Skrip di bawahnya berjalan sebelum halaman tergambar, jadi layar ini tidak sempat berkedip.
--}}
<div id="layar-masuk" role="status" aria-label="Memuat aplikasi"
    class="fixed inset-0 z-[100] flex flex-col items-center bg-white px-4 pt-[100px] transition-opacity duration-500 ease-out dark:bg-navy-950">
    <x-ui.logo class="w-full max-w-[200px] py-5" />
    <p class="text-[2rem] leading-tight font-bold text-[#ff9900]">SIFKUMDA HST</p>
    <div class="mt-2.5 w-fit max-w-full">
        <p class="ketik-masuk text-[clamp(0.875rem,4.4vw,1.5rem)] text-slate-700 dark:text-slate-200">Sistem Fasilitasi Produk Hukum Daerah<span class="ketik-tutup bg-white dark:bg-navy-950" aria-hidden="true"><span class="ketik-kursor"></span></span></p>
    </div>
</div>

<script>
    (() => {
        const layar = document.getElementById('layar-masuk');
        let sudahTampil = true;

        try {
            sudahTampil = sessionStorage.getItem('splash-shown') !== null;
            sessionStorage.setItem('splash-shown', '1');
        } catch (e) {}

        if (sudahTampil) {
            layar.remove();
            return;
        }

        // Selesai mengetik (2 detik), lalu memudar.
        setTimeout(() => {
            layar.classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => layar.remove(), 500);
        }, 2000);
    })();
</script>
