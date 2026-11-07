<div class="mx-auto max-w-3xl">
    <x-ui.page-header title="Pengaturan Naskah" description="Nama pejabat yang dicetak pada bagian penutup naskah baru." icon="gear" :back="route('v2.beranda')" />

    <form wire:submit="simpan" class="space-y-5 rounded-2xl bg-white p-6 shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
        <x-form.field label="Nama Bupati" for="namaBupati" error="namaBupati" required>
            <x-form.input id="namaBupati" wire:model="namaBupati" placeholder="Contoh: H. Nama Lengkap, S.H." />
        </x-form.field>

        <x-form.field label="Nama Sekretaris Daerah" for="namaSekda" error="namaSekda" required>
            <x-form.input id="namaSekda" wire:model="namaSekda" placeholder="Contoh: H. Nama Lengkap, S.H., M.H." />
        </x-form.field>

        <p class="text-sm text-slate-500 dark:text-slate-400">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Naskah yang sudah dibuat tidak ikut berubah. Bila perlu, perbaiki lewat baris Penutup pada naskahnya.
        </p>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-navy-800">
            <span wire:dirty class="text-sm font-medium text-orange-600 dark:text-orange-300">Ada perubahan yang belum disimpan</span>
            @if ($tersimpan)
                <span wire:dirty.remove class="text-sm font-medium text-emerald-600 dark:text-emerald-300"><i class="bi bi-check2" aria-hidden="true"></i> Tersimpan</span>
            @endif
            <x-ui.button type="submit" icon="check2" loading="simpan">Simpan</x-ui.button>
        </div>
    </form>
</div>
