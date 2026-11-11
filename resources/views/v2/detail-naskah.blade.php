@php
    use App\Models\NaskahBerkas;
    use App\Services\AlurNaskah;
@endphp

<div>
    <x-ui.modal id="modalDetailNaskah" title="Detail Naskah" :description="$naskah?->nomor" size="6xl" on-close="tutup">
        @if ($naskah)
            @php $tahap = $naskah->tahap; @endphp

            @include('v2.partials.tahapan', ['tahap' => $tahap])

            <x-ui.alert :type="$giliranSaya ? 'warning' : 'primary'" class="mt-4" :icon="$giliranSaya ? 'hand-index-thumb' : null">
                @if ($giliranSaya)
                    <strong>Saat ini giliran Anda.</strong>
                @endif
                {{ $tahap->keterangan() }}
                @if (filled($naskah->catatan_tahap))
                    <span class="mt-1 block opacity-90">Catatan: {{ $naskah->catatan_tahap }}</span>
                @endif
            </x-ui.alert>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                    <h4 class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-semibold dark:border-navy-800">
                        <i class="bi bi-file-earmark-text text-primary-500"></i> Informasi Utama
                    </h4>
                    <x-ui.info-list class="px-4">
                        <x-ui.info-item label="Nomor">{{ $naskah->nomor_tampil }}</x-ui.info-item>
                        <x-ui.info-item label="Jenis"><x-ui.status-badge :value="$naskah->jenis" /></x-ui.info-item>
                        <x-ui.info-item label="Tentang">{{ $naskah->tentang }}</x-ui.info-item>
                        <x-ui.info-item label="Tanggal Pengajuan">{{ $naskah->diajukan_at ? tanggal($naskah->diajukan_at, true) : 'Belum diajukan' }}</x-ui.info-item>
                        <x-ui.info-item label="User Pengaju">{{ $naskah->pemrakarsa->nama_user }}</x-ui.info-item>
                        <x-ui.info-item label="Perangkat Daerah">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</x-ui.info-item>
                        <x-ui.info-item label="Nomor Nota">{{ $naskah->nomor_nota ?? '-' }}</x-ui.info-item>
                        <x-ui.info-item label="Tanggal Nota">{{ tanggal($naskah->tanggal_nota) }}</x-ui.info-item>
                    </x-ui.info-list>
                </section>

                <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                    <h4 class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-semibold dark:border-navy-800">
                        <i class="bi bi-journal-check text-emerald-500"></i> Hasil Penelitian
                    </h4>
                    <x-ui.info-list class="px-4">
                        <x-ui.info-item label="Tahap Saat Ini"><x-ui.badge :color="$tahap->warna()">{{ $tahap->label() }}</x-ui.badge></x-ui.info-item>
                        <x-ui.info-item label="Peneliti">{{ $naskah->peneliti->nama_user ?? 'Belum ditentukan' }}</x-ui.info-item>
                        <x-ui.info-item label="Baris Matriks">{{ $total }} baris</x-ui.info-item>
                        <x-ui.info-item label="Belum Diperiksa">{{ $belum }} baris</x-ui.info-item>
                        <x-ui.info-item label="Diubah Peneliti">{{ $diubah }} baris</x-ui.info-item>
                        <x-ui.info-item label="Perlu Perbaikan PD">{{ $perlu }} baris</x-ui.info-item>
                        <x-ui.info-item label="Nota Dinas">
                            @if ($naskah->notaDinas && $tahap->langkah() >= 4 && $bisaUnduh)
                                <x-ui.file-link :href="route('v2.naskah.cetak', [$naskah, 'nota-dinas'])" path="nota.pdf" :label="$naskah->notaDinas->nomor_nota" />
                            @elseif ($naskah->notaDinas && $tahap->langkah() >= 4)
                                {{ $naskah->notaDinas->nomor_nota }}
                            @else
                                <span class="text-slate-400 italic dark:text-slate-500">Belum terbit</span>
                            @endif
                        </x-ui.info-item>
                        @foreach (AlurNaskah::TAHAPAN_TTD as $kunci => $nama)
                            @if (isset($naskah->tahapan_ttd[$kunci]))
                                <x-ui.info-item :label="$nama">{{ tanggal($naskah->tahapan_ttd[$kunci]) }}</x-ui.info-item>
                            @endif
                        @endforeach
                        @if ($naskah->dokumentasi_id)
                            <x-ui.info-item label="Arsip">
                                <x-ui.file-link :href="route('jdih.show', $naskah->dokumentasi_id)" label="Lihat di JDIH" />
                            </x-ui.info-item>
                        @endif
                    </x-ui.info-list>
                </section>
            </div>

            @if ($bisaUnduh)
            <section class="mt-5 rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                <h4 class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-semibold dark:border-navy-800">
                    <i class="bi bi-paperclip text-amber-500"></i> Berkas
                </h4>
                <x-ui.info-list class="px-4">
                    @foreach ($berkas as $file)
                        <x-ui.info-item :label="NaskahBerkas::LABEL[$file->jenis]" wire:key="berkas-{{ $file->id }}">
                            <x-ui.file-link :href="route('v2.naskah.berkas', [$naskah, $file])" :path="$file->nama_asli" :label="$file->nama_asli" />
                        </x-ui.info-item>
                    @endforeach
                    <x-ui.info-item label="Naskah Saat Ini">
                        <span class="flex flex-wrap gap-x-4 gap-y-1">
                            <x-ui.file-link :href="route('v2.naskah.cetak', [$naskah, 'naskah-word'])" path="naskah.docx" label="Word" />
                            <x-ui.file-link :href="route('v2.naskah.cetak', [$naskah, 'naskah-pdf'])" path="naskah.pdf" label="PDF" />
                        </span>
                    </x-ui.info-item>
                    <x-ui.info-item label="Matriks Saat Ini">
                        <x-ui.file-link :href="route('v2.naskah.cetak', [$naskah, 'matriks-pdf'])" path="matriks.pdf" label="PDF" />
                    </x-ui.info-item>
                </x-ui.info-list>
            </section>
            @endif
        @else
            <div class="flex flex-col items-center gap-3 py-16 text-slate-500">
                <x-ui.spinner class="size-8 text-primary-500" />
                <p class="text-sm">Sedang memuat data, harap tunggu...</p>
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Tutup</x-ui.button>
            @if ($naskah)
                @if (auth()->user()->pemantau())
                    <x-ui.button icon="vector-pen" :href="route('v2.naskah.pantau', $naskah)">Pantau Tanda Tangan</x-ui.button>
                @elseif ($naskah->tahap->tahapAkhir())
                    {{-- Isi sudah terkunci: yang dikerjakan tinggal nota dinas, tanda tangan, dan arsip. --}}
                    <x-ui.button variant="outline" icon="table" :href="route('v2.naskah.kerja', $naskah)">Lihat Matriks</x-ui.button>
                    <x-ui.button :variant="$giliranSaya ? 'primary' : 'outline-primary'" icon="vector-pen" :href="route('v2.naskah.pantau', $naskah)">{{ $giliranSaya ? $naskah->tahap->labelKerjakan() : 'Nota Dinas & Tanda Tangan' }}</x-ui.button>
                @elseif ($giliranSaya)
                    <x-ui.button icon="pencil-square" :href="route('v2.naskah.kerja', $naskah)">Kerjakan Naskah</x-ui.button>
                @else
                    <x-ui.button variant="outline-primary" icon="table" :href="route('v2.naskah.kerja', $naskah)">Lihat Matriks</x-ui.button>
                @endif
            @endif
        </x-slot:footer>
    </x-ui.modal>
</div>
