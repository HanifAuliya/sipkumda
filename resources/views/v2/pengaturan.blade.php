<div class="mx-auto max-w-2xl px-4 py-8">
    <h1 class="text-2xl font-extrabold tracking-tight text-navy-900 dark:text-white">Pengaturan</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Nama pejabat untuk bagian penutup naskah baru. Naskah yang sudah dibuat tidak berubah; perbaiki lewat baris Penutup bila perlu.</p>

    <form wire:submit="simpan" class="mt-6 space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
        <x-form.field label="Nama Bupati" for="namaBupati" error="namaBupati" required>
            <x-form.input id="namaBupati" wire:model="namaBupati" />
        </x-form.field>

        <x-form.field label="Nama Sekretaris Daerah" for="namaSekda" error="namaSekda" required>
            <x-form.input id="namaSekda" wire:model="namaSekda" />
        </x-form.field>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-navy-800">
            @if ($tersimpan)
                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-300"><i class="bi bi-check2"></i> Tersimpan</span>
            @endif
            <x-ui.button type="submit" icon="check2" loading="simpan">Simpan</x-ui.button>
        </div>
    </form>
</div>
