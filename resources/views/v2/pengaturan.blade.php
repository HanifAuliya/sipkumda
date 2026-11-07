<div class="mx-auto max-w-3xl">
    <x-ui.page-header title="Pengaturan Naskah" description="Nama pejabat yang dicetak pada bagian penutup naskah baru." icon="gear" :back="route('v2.beranda')" />

    <x-ui.card title="Nama Pejabat">
        @unless ($mengubah)
            <x-slot:actions>
                <x-ui.button variant="outline" size="sm" icon="pencil" wire:click="mulaiUbah" loading="mulaiUbah">Ubah</x-ui.button>
            </x-slot:actions>
        @endunless

        @if ($mengubah)
            <form wire:submit="simpan" class="space-y-5">
                <x-form.field label="Nama Bupati" for="namaBupati" error="namaBupati" required>
                    <x-form.input id="namaBupati" wire:model="namaBupati" placeholder="Contoh: H. Nama Lengkap, S.H." x-init="$el.focus()" />
                </x-form.field>

                <x-form.field label="Nama Sekretaris Daerah" for="namaSekda" error="namaSekda" required>
                    <x-form.input id="namaSekda" wire:model="namaSekda" placeholder="Contoh: H. Nama Lengkap, S.H., M.H." />
                </x-form.field>

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    Naskah yang sudah dibuat tidak ikut berubah. Bila perlu, perbaiki lewat baris Penutup pada naskahnya.
                </p>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5 dark:border-navy-800">
                    <x-ui.button variant="outline" wire:click="batal">Batal</x-ui.button>
                    <x-ui.button type="submit" icon="check2" loading="simpan">Simpan</x-ui.button>
                </div>
            </form>
        @else
            <x-ui.info-list>
                <x-ui.info-item label="Nama Bupati">{{ $namaBupati ?: 'Belum diisi' }}</x-ui.info-item>
                <x-ui.info-item label="Nama Sekretaris Daerah">{{ $namaSekda ?: 'Belum diisi' }}</x-ui.info-item>
            </x-ui.info-list>
        @endif
    </x-ui.card>
</div>
