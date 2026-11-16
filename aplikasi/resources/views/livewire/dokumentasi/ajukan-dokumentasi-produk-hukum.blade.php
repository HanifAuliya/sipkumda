<div>
    @if ($bisaTambah)
        @php $dariSistem = $sumber === 'sistem'; @endphp
        <x-ui.modal id="modalTambahDokumentasi" size="2xl" static on-close="resetForm"
            :title="$dariSistem ? 'Arsipkan Naskah dari Sistem' : 'Tambah Arsip Lama'"
            :description="$dariSistem
                ? 'Naskah SIFKUMDA yang paraf dan tanda tangannya sudah lengkap. Jenis, judul, dan perangkat daerah diambil dari naskahnya.'
                : 'Produk hukum yang ditetapkan sebelum ada sistem, sehingga tidak terkait naskah mana pun.'">
            <form id="tambahDokumentasiForm" wire:submit="store" class="space-y-5">
                @if ($dariSistem)
                    <x-form.field label="Naskah" for="naskahId" error="naskahId" required>
                        @if ($naskahList->isEmpty())
                            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-navy-800 dark:text-slate-400">
                                Belum ada naskah yang siap diarsipkan. Naskah bisa diarsipkan setelah semua paraf dan tanda tangan selesai.
                            </div>
                        @else
                            <x-form.select id="naskahId" wire:model.live="naskahId">
                                <option value="" hidden>Pilih naskah yang tanda tangannya lengkap</option>
                                @foreach ($naskahList as $n)
                                    <option value="{{ $n->id }}">{{ $n->nomor_tampil }} · {{ \Illuminate\Support\Str::limit($n->tentang, 80) }}</option>
                                @endforeach
                            </x-form.select>
                        @endif
                    </x-form.field>

                    @if ($naskahTerpilih)
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-navy-800">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-navy-900 dark:text-white">{{ $naskahTerpilih->nomor_tampil }}</span>
                                <x-ui.status-badge :value="$naskahTerpilih->jenis" />
                            </div>
                            <p class="mt-1.5 font-medium text-slate-700 dark:text-slate-200">{{ $naskahTerpilih->tentang }}</p>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                {{ $naskahTerpilih->pemrakarsa?->perangkatDaerah?->nama_perangkat_daerah ?? '-' }}
                            </p>
                        </div>
                    @endif
                @else
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
                @endif

                @if (! $dariSistem || $naskahTerpilih)
                @include('livewire.dokumentasi.partials.nomor-fields', ['tahunTetap' => null, 'beritaWajib' => $beritaWajib])

                <x-form.field label="Tanggal Penetapan" for="tanggal_penetapan" error="tanggal_penetapan" required>
                    <x-form.input type="date" id="tanggal_penetapan" wire:model="tanggal_penetapan" />
                </x-form.field>

                <x-form.upload model="file_produk_hukum" :file="$file_produk_hukum" label="File Produk Hukum" accept="application/pdf"
                    hint="Produk hukum bertanda tangan dalam PDF, maks. 20MB." required />
                <x-ui.loading target="file_produk_hukum">Mengunggah file...</x-ui.loading>
                @endif
            </form>

            <x-slot:footer>
                <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
                <x-ui.button type="submit" form="tambahDokumentasiForm" :icon="$dariSistem ? 'archive' : 'save'" loading="store"
                    :disabled="$dariSistem && ! $naskahTerpilih">{{ $dariSistem ? 'Arsipkan' : 'Simpan' }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
