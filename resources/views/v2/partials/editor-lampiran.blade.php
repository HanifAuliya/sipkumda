{{--
    Editor lampiran seperti Word (resources/js/alpine/editor-lampiran.js). Isinya terikat ke `teks` milik baris-kerja.
    Dibungkus elemen ber-x-ref supaya baris-kerja bisa memfokuskannya; wire:ignore karena isinya dibangun CKEditor.
--}}
<div x-data="editorLampiran()" wire:ignore class="editor-lampiran rounded-lg">
    <p x-show="memuat" class="rounded-lg bg-slate-50 px-3.5 py-6 text-center text-sm text-slate-500 dark:bg-navy-800/60 dark:text-slate-400">
        <i class="bi bi-hourglass-split" aria-hidden="true"></i> Memuat editor…
    </p>
    <p x-show="gagal" x-cloak class="rounded-lg bg-rose-50 px-3.5 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">
        Editor gagal dimuat. Muat ulang halaman.
    </p>
    <div x-ref="wadah"></div>
</div>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
    Atur seperti di Word: rata, tebal, daftar bernomor, dan tabel (gabung sel lewat tombol tabel). Lampiran dari Word bisa disalin lalu ditempel (Ctrl+V) dengan formatnya.
</p>
