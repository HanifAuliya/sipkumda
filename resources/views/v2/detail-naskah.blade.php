@php
    use App\Models\NaskahBerkas;
    use App\Services\AlurNaskah;
@endphp

<div>
    <x-ui.modal id="modalDetailNaskah" title="Detail Naskah" :description="$naskah?->nomor" size="5xl" on-close="tutup">
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
                    <h4 class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-navy-900 dark:border-navy-800 dark:text-white">
                        <i class="bi bi-file-earmark-text text-slate-400"></i> Informasi Utama
                    </h4>
                    <x-ui.info-list class="px-5 [&>div]:sm:grid-cols-[10.5rem_minmax(0,1fr)]!">
                        <x-ui.info-item label="Jenis"><x-ui.status-badge :value="$naskah->jenis" /></x-ui.info-item>
                        <x-ui.info-item label="Tentang">{{ $naskah->tentang }}</x-ui.info-item>
                        <x-ui.info-item label="Tanggal Pengajuan">{{ $naskah->diajukan_at ? tanggal($naskah->diajukan_at, true) : 'Belum diajukan' }}</x-ui.info-item>
                        <x-ui.info-item label="Diajukan oleh">{{ $naskah->pemrakarsa->nama_user }}</x-ui.info-item>
                        <x-ui.info-item label="Perangkat Daerah">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</x-ui.info-item>
                        <x-ui.info-item label="Nomor Nota Dinas PD">{{ $naskah->nomor_nota ?? '-' }}</x-ui.info-item>
                        <x-ui.info-item label="Tanggal Nota Dinas PD">{{ tanggal($naskah->tanggal_nota) }}</x-ui.info-item>
                    </x-ui.info-list>
                </section>

                <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                    <h4 class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-navy-900 dark:border-navy-800 dark:text-white">
                        <i class="bi bi-activity text-slate-400"></i> Perkembangan
                    </h4>
                    <x-ui.info-list class="px-5 [&>div]:sm:grid-cols-[10.5rem_minmax(0,1fr)]!">
                        <x-ui.info-item label="Tahap Saat Ini"><x-ui.badge :color="$tahap->warna()">{{ $tahap->label() }}</x-ui.badge></x-ui.info-item>
                        <x-ui.info-item label="Peneliti">{{ $naskah->peneliti->nama_user ?? 'Belum ditentukan' }}</x-ui.info-item>
                        {{-- Empat angka baris diringkas: kemajuan pemeriksaan, lalu hasilnya. --}}
                        @if ($total)
                            @php $persen = (int) round(($total - $belum) / $total * 100); @endphp
                            <x-ui.info-item label="Pemeriksaan Rumusan">
                                <span class="flex items-center gap-3">
                                    <span class="h-1.5 w-24 shrink-0 overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800">
                                        <span class="block h-full rounded-full bg-emerald-500" style="width: {{ $persen }}%"></span>
                                    </span>
                                    {{ $total - $belum }} dari {{ $total }} baris
                                </span>
                            </x-ui.info-item>
                            <x-ui.info-item label="Hasil">
                                {{ $diubah }} diubah<span class="text-slate-400"> · </span>{{ $perlu }} perlu perbaikan PD
                            </x-ui.info-item>
                        @endif
                        <x-ui.info-item label="Nota Dinas">
                            @if ($naskah->notaDinas && $tahap->langkah() >= 4 && $bisaUnduh)
                                <x-ui.file-link :href="route('naskah.cetak', [$naskah, 'nota-dinas'])" path="nota.pdf" :label="$naskah->notaDinas->nomor_nota" />
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
                <h4 class="flex items-center gap-2 border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-navy-900 dark:border-navy-800 dark:text-white">
                    <i class="bi bi-paperclip text-slate-400"></i> Berkas
                </h4>
                <x-ui.info-list class="px-5 [&>div]:sm:grid-cols-[10.5rem_minmax(0,1fr)]! [&>div]:items-center">
                    @foreach ($berkas as $file)
                        <x-ui.info-item :label="NaskahBerkas::LABEL[$file->jenis]" wire:key="berkas-{{ $file->id }}">
                            <x-ui.file-link :href="route('naskah.berkas', [$naskah, $file])" :path="$file->nama_asli" :label="$file->nama_asli" />
                        </x-ui.info-item>
                    @endforeach
                    <x-ui.info-item label="Naskah Saat Ini">
                        <span class="flex flex-wrap gap-x-4 gap-y-1">
                            <x-ui.file-link :href="route('naskah.cetak', [$naskah, 'naskah-word'])" path="naskah.docx" label="Word" />
                            <x-ui.file-link :href="route('naskah.cetak', [$naskah, 'naskah-pdf'])" path="naskah.pdf" label="PDF" />
                        </span>
                    </x-ui.info-item>
                    <x-ui.info-item label="Matriks Saat Ini">
                        <x-ui.file-link :href="route('naskah.cetak', [$naskah, 'matriks-pdf'])" path="matriks.pdf" label="PDF" />
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
                    <x-ui.button icon="vector-pen" :href="route('naskah.pantau', $naskah)">Pantau Tanda Tangan</x-ui.button>
                @elseif ($naskah->tahap->tahapAkhir())
                    {{-- Isi sudah terkunci: yang dikerjakan tinggal nota dinas, tanda tangan, dan arsip. --}}
                    <x-ui.button variant="outline" icon="table" :href="route('naskah.kerja', $naskah)">Lihat Matriks</x-ui.button>
                    <x-ui.button :variant="$giliranSaya ? 'primary' : 'outline-primary'" icon="vector-pen" :href="route('naskah.pantau', $naskah)">{{ $giliranSaya ? $naskah->tahap->labelKerjakan() : 'Nota Dinas & Tanda Tangan' }}</x-ui.button>
                @elseif ($giliranSaya)
                    <x-ui.button icon="pencil-square" :href="route('naskah.kerja', $naskah)">Kerjakan Naskah</x-ui.button>
                @else
                    <x-ui.button variant="outline-primary" icon="table" :href="route('naskah.kerja', $naskah)">Lihat Matriks</x-ui.button>
                @endif
            @endif
        </x-slot:footer>
    </x-ui.modal>
</div>
