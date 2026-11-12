<div>
    @if ($bisaTambah)
        <x-ui.modal id="modalTambahDokumentasi" title="Tambah Arsip Dokumentasi" size="2xl" static on-close="resetForm"
            description="Produk hukum yang ditetapkan sebelum ada sistem. Produk hukum dari naskah diarsipkan lewat tab Menunggu Arsip.">
            <form id="tambahDokumentasiForm" wire:submit="store" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field label="Jenis Produk Hukum" for="jenis_dokumentasi" error="jenis_dokumentasi" required>
                        <x-form.select id="jenis_dokumentasi" wire:model.live="jenis_dokumentasi">
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
                <x-form.field label="Tentang (Judul Produk Hukum)" for="tentang_dokumentasi" error="tentang_dokumentasi" required>
                    <x-form.judul id="tentang_dokumentasi" wire:model="tentang_dokumentasi" placeholder="Contoh: Pengelolaan Sampah" />
                </x-form.field>

                @include('livewire.dokumentasi.partials.nomor-fields', ['tahunTetap' => null, 'beritaWajib' => true])

                <x-form.field label="Tanggal Penetapan" for="tanggal_penetapan" error="tanggal_penetapan" required>
                    <x-form.input type="date" id="tanggal_penetapan" wire:model="tanggal_penetapan" />
                </x-form.field>

                <x-form.upload model="file_produk_hukum" :file="$file_produk_hukum" label="File Produk Hukum" accept="application/pdf"
                    hint="Berita daerah dalam PDF, maks. 20MB." required />
                <x-ui.loading target="file_produk_hukum">Mengunggah file...</x-ui.loading>
            </form>

            <x-slot:footer>
                <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
                <x-ui.button type="submit" form="tambahDokumentasiForm" icon="save" loading="store">Simpan</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
