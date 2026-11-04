<div>
    @if ($bisaTambah)
        <x-ui.modal id="modalTambahDokumentasi" title="Tambah Arsip Dokumentasi" size="2xl" static on-close="resetForm"
            :description="$mode === 'manual' ? 'Arsip produk hukum yang ditetapkan sebelum ada sistem.' : 'Arsip dari rancangan yang fasilitasinya telah diterima.'">
            <form id="tambahDokumentasiForm" wire:submit="store" class="space-y-5">
                @if ($mode === 'rancangan')
                    <x-form.field label="Rancangan" for="rancanganId" error="rancanganId" required>
                        <x-form.select id="rancanganId" wire:model.live="rancanganId" :disabled="$rancanganList->isEmpty()">
                            @if ($rancanganList->isEmpty())
                                <option value="">Tidak ada rancangan yang menunggu diarsipkan</option>
                            @else
                                <option value="">Pilih rancangan</option>
                                @foreach ($rancanganList as $fasilitasi)
                                    <option value="{{ $fasilitasi->id }}">{{ $fasilitasi->rancangan->no_rancangan }} — {{ Str::limit($fasilitasi->rancangan->tentang, 70) }}</option>
                                @endforeach
                            @endif
                        </x-form.select>
                    </x-form.field>
                @else
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.field label="Jenis Produk Hukum" for="jenis_dokumentasi" error="jenis_dokumentasi" required>
                            <x-form.select id="jenis_dokumentasi" wire:model="jenis_dokumentasi">
                                <option value="" hidden>Pilih jenis</option>
                                <option value="Peraturan Bupati">Peraturan Bupati</option>
                                <option value="Keputusan Bupati">Keputusan Bupati</option>
                            </x-form.select>
                        </x-form.field>
                        <x-form.field label="Perangkat Daerah" for="perangkat_daerah_id" error="perangkat_daerah_id" required>
                            <x-form.select id="perangkat_daerah_id" wire:model="perangkat_daerah_id">
                                <option value="" hidden>Pilih perangkat daerah</option>
                                @foreach ($perangkatList as $perangkat)
                                    <option value="{{ $perangkat->id }}">{{ $perangkat->nama_perangkat_daerah }}</option>
                                @endforeach
                            </x-form.select>
                        </x-form.field>
                    </div>
                    <x-form.field label="Tentang" for="tentang_dokumentasi" error="tentang_dokumentasi" required>
                        <x-form.textarea id="tentang_dokumentasi" wire:model="tentang_dokumentasi" rows="2" placeholder="Contoh: Pengelolaan Sampah" />
                    </x-form.field>
                @endif

                @if ($mode === 'manual' || $rancanganId)
                    @include('livewire.dokumentasi.partials.nomor-fields', [
                        'tahunTetap' => $mode === 'rancangan' ? now()->year : null,
                        'beritaWajib' => $mode === 'manual',
                    ])

                    <x-form.field label="Tanggal Penetapan" for="tanggal_penetapan" error="tanggal_penetapan" required>
                        <x-form.input type="date" id="tanggal_penetapan" wire:model="tanggal_penetapan" />
                    </x-form.field>

                    <x-form.upload model="file_produk_hukum" :file="$file_produk_hukum" label="File Produk Hukum" accept="application/pdf"
                        hint="Berita daerah dalam PDF, maks. 20MB." required />
                    <x-ui.loading target="file_produk_hukum">Mengunggah file...</x-ui.loading>
                @endif
            </form>

            <x-slot:footer>
                <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
                <x-ui.button type="submit" form="tambahDokumentasiForm" icon="save" loading="store"
                    :disabled="$mode === 'rancangan' && ! $rancanganId">Simpan</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
