<div class="mx-auto max-w-3xl">
    <x-ui.page-header title="Pengaturan Naskah" description="Nama pejabat pada penutup naskah dan urutan paraf serta tanda tangan basah." icon="gear" :back="route('dashboard')" />

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

    <x-ui.card title="Urutan Tanda Tangan" description="Tahapan paraf dan tanda tangan basah yang dicatat sesudah nota dinas terbit." class="mt-6">
        @if ($bolehAturTahapan && ! $mengubahTahapan)
            <x-slot:actions>
                <x-ui.button variant="outline" size="sm" icon="pencil" wire:click="mulaiUbahTahapan" loading="mulaiUbahTahapan">Ubah</x-ui.button>
            </x-slot:actions>
        @endif

        @if ($mengubahTahapan)
            <form wire:submit="simpanTahapan" class="space-y-4">
                @error('tahapan')
                    <x-ui.alert type="danger">{{ $message }}</x-ui.alert>
                @enderror

                <ol class="space-y-2">
                    @foreach ($tahapan as $i => $t)
                        <li class="flex items-start gap-2" wire:key="tahapan-{{ $i }}-{{ $t['kunci'] }}">
                            <span class="mt-2 grid size-7 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600 dark:bg-navy-800 dark:text-slate-300">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <x-form.input wire:model="tahapan.{{ $i }}.nama" maxlength="60" placeholder="Contoh: Paraf Asisten" aria-label="Nama tahapan {{ $loop->iteration }}" />
                                @error("tahapan.{$i}.nama")
                                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <x-ui.button size="sm" variant="ghost" square icon="arrow-up" title="Naikkan" aria-label="Naikkan" wire:click="geserTahapan({{ $i }}, -1)" :disabled="$loop->first" />
                                <x-ui.button size="sm" variant="ghost" square icon="arrow-down" title="Turunkan" aria-label="Turunkan" wire:click="geserTahapan({{ $i }}, 1)" :disabled="$loop->last" />
                                <x-ui.button size="sm" variant="ghost" square icon="trash" title="Hapus" aria-label="Hapus tahapan" class="hover:text-rose-600" wire:click="hapusTahapan({{ $i }})" :disabled="count($tahapan) === 1" />
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="flex flex-wrap gap-2">
                    @if (count($tahapan) < 8)
                        <x-ui.button variant="outline" size="sm" icon="plus-lg" wire:click="tambahTahapan">Tambah Tahapan</x-ui.button>
                    @endif
                    <x-ui.button variant="ghost" size="sm" icon="arrow-counterclockwise" wire:click="bawaanTahapan">Pakai Urutan Bawaan</x-ui.button>
                </div>

                @if ($sedangBerjalan)
                    <x-ui.alert type="warning" icon="exclamation-triangle">
                        Ada {{ $sedangBerjalan }} naskah yang sedang dalam tahap tanda tangan atau menunggu arsip. Urutan baru langsung berlaku untuk naskah itu juga;
                        tanggal yang sudah dicatat untuk tahapan yang dihapus tidak dipakai lagi.
                    </x-ui.alert>
                @endif

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-5 dark:border-navy-800">
                    <x-ui.button variant="outline" wire:click="batalTahapan">Batal</x-ui.button>
                    <x-ui.button type="submit" icon="check2" loading="simpanTahapan">Simpan</x-ui.button>
                </div>
            </form>
        @else
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-2 text-sm">
                @foreach ($urutanTtd as $nama)
                    <li class="flex items-center gap-2">
                        <span class="grid size-6 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600 dark:bg-navy-800 dark:text-slate-300">{{ $loop->iteration }}</span>
                        <span class="font-medium text-navy-900 dark:text-white">{{ $nama }}</span>
                        @unless ($loop->last)
                            <i class="bi bi-chevron-right text-xs text-slate-300" aria-hidden="true"></i>
                        @endunless
                    </li>
                @endforeach
            </ol>
            @unless ($bolehAturTahapan)
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Hanya Admin yang bisa mengubah urutan ini.</p>
            @endunless
        @endif
    </x-ui.card>
</div>
