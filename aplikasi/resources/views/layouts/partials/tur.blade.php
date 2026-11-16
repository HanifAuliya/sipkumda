{{--
    Tur pengenalan di Dashboard (App\Support\TurPengenalan, resources/js/alpine/tur.js).
    Muncul sekali per akun, atau lagi lewat ?tur=1.
--}}
@if (\App\Support\TurPengenalan::tampil(auth()->user()))
    @php $langkah = \App\Support\TurPengenalan::langkah(auth()->user()); @endphp

    <div x-data="turPengenalan(@js($langkah), @js(route('tur.selesai')))" x-on:keydown.window="tombol($event)"
        x-show="buka" x-cloak class="fixed inset-0 z-[90]" role="dialog" aria-modal="true" aria-labelledby="tur-judul" data-tur-pengenalan>

        {{-- Latar gelap. Saat ada target, gelapnya dibuat oleh bayangan kotak sorotan supaya targetnya tetap terang. --}}
        <div class="absolute inset-0 bg-navy-950/60 transition-opacity duration-300" x-show="!sorot" x-transition.opacity></div>
        <div x-show="sorot" class="pointer-events-none absolute rounded-xl ring-2 ring-primary-400 transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
            style="box-shadow: 0 0 0 9999px rgb(10 18 40 / 0.6)"
            x-bind:style="sorot && { top: sorot.top + 'px', left: sorot.left + 'px', width: sorot.width + 'px', height: sorot.height + 'px' }"></div>
        {{-- Klik di luar kartu tidak menutup tur, supaya tidak hilang tanpa sengaja. --}}
        <div class="absolute inset-0"></div>

        <div x-ref="kartu"
            class="absolute w-[calc(100%-2rem)] overflow-hidden rounded-2xl bg-white p-5 pt-6 shadow-2xl ring-1 ring-slate-900/5 transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] sm:w-[360px] dark:bg-navy-900 dark:ring-white/10"
            x-bind:class="hp ? 'inset-x-4 bottom-4' : (!sorot ? 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2' : '')"
            x-bind:style="!hp && sorot ? { top: kartu.top + 'px', left: kartu.left + 'px' } : {}">

            {{-- Garis kemajuan di tepi atas kartu. --}}
            <div class="absolute inset-x-0 top-0 h-1 overflow-hidden rounded-t-2xl bg-slate-100 dark:bg-navy-800" aria-hidden="true">
                <div class="h-full bg-primary-500 transition-[width] duration-300" x-bind:style="{ width: ((i + 1) / langkah.length * 100) + '%' }"></div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-200"
                    x-text="(i + 1) + ' dari ' + langkah.length"></span>
                <button type="button" class="text-sm font-medium text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200" x-on:click="selesai()">Lewati tur</button>
            </div>

            <h2 id="tur-judul" class="mt-3 text-lg font-bold text-navy-900 dark:text-white" x-text="sekarang?.judul"></h2>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300" x-text="isi"></p>
            <p class="mt-2.5 flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500 dark:bg-navy-800 dark:text-slate-400" x-show="menuTersembunyi">
                <i class="bi bi-list text-base" aria-hidden="true"></i> Menu ini dibuka lewat tombol di kiri atas.
            </p>

            <div class="mt-5 flex items-center justify-end gap-2">
                <x-ui.button variant="outline" size="sm" icon="arrow-left" x-show="i > 0" x-on:click="ke(i - 1)">Sebelumnya</x-ui.button>
                <x-ui.button size="sm" x-on:click="lanjut()">
                    <span x-text="terakhir ? 'Mulai memakai' : (i === 0 ? 'Mulai tur' : 'Selanjutnya')">Selanjutnya</span>
                    <i class="bi bi-arrow-right" aria-hidden="true" x-show="!terakhir"></i>
                </x-ui.button>
            </div>
        </div>
    </div>
@endif
