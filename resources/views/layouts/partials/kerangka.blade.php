{{-- Kerangka halaman yang tampil bila server lambat saat berpindah menu (resources/js/navigasi.js). --}}
<div class="mx-auto w-full max-w-[1600px] animate-pulse" role="status" aria-label="Memuat halaman" data-kerangka-halaman>
    <div class="mb-6 flex items-center gap-3 rounded-2xl bg-white p-5 shadow-card sm:gap-4 sm:p-6 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
        <div class="kerangka-blok size-12 shrink-0 rounded-xl"></div>
        <div class="min-w-0 flex-1 space-y-2.5">
            <div class="kerangka-blok h-5 w-48 max-w-full"></div>
            <div class="kerangka-blok h-3.5 w-80 max-w-full"></div>
        </div>
        <div class="kerangka-blok hidden h-10 w-28 rounded-lg sm:block"></div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-card sm:p-6 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="kerangka-blok h-11 flex-1 rounded-lg"></div>
            <div class="kerangka-blok h-11 rounded-lg sm:w-48"></div>
        </div>

        <div class="mt-6 space-y-5">
            @foreach ([72, 58, 66, 50, 62] as $lebar)
                <div class="flex items-center gap-4 border-b border-slate-100 pb-5 last:border-0 last:pb-0 dark:border-navy-800">
                    <div class="min-w-0 flex-1 space-y-2.5">
                        <div class="kerangka-blok h-4 w-32"></div>
                        <div class="kerangka-blok h-3.5 max-w-full" style="width: {{ $lebar }}%"></div>
                    </div>
                    <div class="kerangka-blok size-9 shrink-0 rounded-lg"></div>
                </div>
            @endforeach
        </div>
    </div>
</div>
