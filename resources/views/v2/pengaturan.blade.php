<div class="mx-auto max-w-3xl">
    <x-ui.page-header title="Pengaturan Naskah" description="Nama pejabat untuk bagian penutup naskah baru. Naskah yang sudah dibuat tidak berubah; perbaiki lewat baris Penutup bila perlu." icon="gear" :back="route('v2.beranda')" />

    <form wire:submit="simpan" class="space-y-5 rounded-2xl bg-white p-6 shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
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
