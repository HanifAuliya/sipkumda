@php
    use App\Models\NaskahBerkas;
    use App\Services\AlurNaskah;
@endphp

<div>
    <x-ui.modal id="modalDetailNaskah" title="Detail Naskah" :description="$naskah?->nomor_tampil" size="5xl" on-close="tutup">
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

            @php
                $kolom = 'px-5 [&>div]:sm:grid-cols-[9.5rem_minmax(0,1fr)]!';
                $judulKartu = 'flex items-center gap-2 border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-navy-900 dark:border-navy-800 dark:text-white';
                $sekunder = 'block text-sm text-slate-500 dark:text-slate-400';
                $notaTerbit = $naskah->notaDinas && $tahap->langkah() >= 4;
            @endphp

            {{-- Dua kartu berisi kurang lebih sama banyak, supaya tingginya seimbang. --}}
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                    <h4 class="{{ $judulKartu }}"><i class="bi bi-file-earmark-text text-slate-400"></i> Informasi Utama</h4>
                    <x-ui.info-list :class="$kolom">
                        <x-ui.info-item label="Jenis"><x-ui.status-badge :value="$naskah->jenis" /></x-ui.info-item>
                        <x-ui.info-item label="Tentang"><span class="font-medium text-navy-900 dark:text-white">{{ $naskah->tentang }}</span></x-ui.info-item>
                        <x-ui.info-item label="Perangkat Daerah">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</x-ui.info-item>
                        <x-ui.info-item label="Diajukan oleh">
                            {{ $naskah->pemrakarsa->nama_user }}
                            <span class="{{ $sekunder }}">{{ $naskah->diajukan_at ? tanggal($naskah->diajukan_at, true) : 'Belum diajukan' }}</span>
                        </x-ui.info-item>
                        <x-ui.info-item label="Nota Dinas PD">
                            {{ $naskah->nomor_nota ?? '-' }}
                            <span class="{{ $sekunder }}">{{ tanggal($naskah->tanggal_nota) }}</span>
                        </x-ui.info-item>
                    </x-ui.info-list>
                </section>

                <section class="rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                    <h4 class="{{ $judulKartu }}"><i class="bi bi-activity text-slate-400"></i> Perkembangan</h4>
                    <x-ui.info-list :class="$kolom">
                        <x-ui.info-item label="Tahap"><x-ui.badge :color="$tahap->warna()">{{ $tahap->label() }}</x-ui.badge></x-ui.info-item>
                        <x-ui.info-item label="Peneliti">{{ $naskah->peneliti->nama_user ?? 'Belum ditentukan' }}</x-ui.info-item>
                        @if ($total)
                            @php $persen = (int) round(($total - $belum) / $total * 100); @endphp
                            <x-ui.info-item label="Pemeriksaan">
                                <span class="flex items-center gap-3">
                                    <span class="h-1.5 w-20 shrink-0 overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800">
                                        <span class="block h-full rounded-full bg-emerald-500" style="width: {{ $persen }}%"></span>
                                    </span>
                                    <span>{{ $total - $belum }} dari {{ $total }} baris</span>
                                </span>
                                <span class="{{ $sekunder }} mt-1">{{ $diubah }} diubah · {{ $perlu }} perlu perbaikan PD</span>
                            </x-ui.info-item>
                        @endif
                        <x-ui.info-item label="Nota Dinas">
                            @if ($notaTerbit && $bisaUnduh)
                                <x-ui.file-link :href="route('naskah.cetak', [$naskah, 'nota-dinas'])" path="nota.pdf" :label="$naskah->notaDinas->nomor_nota" class="min-h-0" />
                            @elseif ($notaTerbit)
                                {{ $naskah->notaDinas->nomor_nota }}
                            @else
                                <span class="text-slate-400 dark:text-slate-500">Belum terbit</span>
                            @endif
                        </x-ui.info-item>
                        @if ($naskah->tahapan_ttd)
                            {{-- Tahapan tanda tangan diringkas dalam satu baris, bukan empat. --}}
                            <x-ui.info-item label="Tanda Tangan">
                                {{ count(array_intersect_key($naskah->tahapan_ttd, AlurNaskah::TAHAPAN_TTD)) }} dari {{ count(AlurNaskah::TAHAPAN_TTD) }} selesai
                                @php $terakhir = collect(AlurNaskah::TAHAPAN_TTD)->filter(fn ($n, $k) => isset($naskah->tahapan_ttd[$k]))->keys()->last(); @endphp
                                @if ($terakhir)
                                    <span class="{{ $sekunder }}">Terakhir: {{ AlurNaskah::TAHAPAN_TTD[$terakhir] }}, {{ tanggal($naskah->tahapan_ttd[$terakhir]) }}</span>
                                @endif
                            </x-ui.info-item>
                        @endif
                        @if ($naskah->dokumentasi_id)
                            <x-ui.info-item label="Arsip">
                                <x-ui.file-link :href="route('jdih.show', $naskah->dokumentasi_id)" label="Lihat di JDIH" class="min-h-0" />
                            </x-ui.info-item>
                        @endif
                    </x-ui.info-list>
                </section>
            </div>

            @if ($bisaUnduh)
                {{-- Setiap berkas satu baris: nama di kiri, tombol unduh di kanan. --}}
                <section class="mt-5 rounded-xl ring-1 ring-slate-100 dark:ring-navy-800">
                    <h4 class="{{ $judulKartu }}"><i class="bi bi-paperclip text-slate-400"></i> Berkas</h4>
                    <ul class="divide-y divide-slate-100 px-5 dark:divide-navy-800">
                        @foreach ($berkas as $file)
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3" wire:key="berkas-{{ $file->id }}">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-navy-900 dark:text-white">{{ NaskahBerkas::LABEL[$file->jenis] }}</p>
                                    <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $file->nama_asli }}</p>
                                </div>
                                <x-ui.button size="sm" variant="outline" icon="download" :href="route('naskah.berkas', [$naskah, $file])">Unduh</x-ui.button>
                            </li>
                        @endforeach
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-navy-900 dark:text-white">Naskah saat ini</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Dibuat dari isi matriks terbaru</p>
                            </div>
                            <div class="flex gap-2">
                                <x-ui.button size="sm" variant="outline" icon="file-earmark-word" :href="route('naskah.cetak', [$naskah, 'naskah-word'])">Word</x-ui.button>
                                <x-ui.button size="sm" variant="outline" icon="file-earmark-pdf" :href="route('naskah.cetak', [$naskah, 'naskah-pdf'])" target="_blank" rel="noopener">PDF</x-ui.button>
                            </div>
                        </li>
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-navy-900 dark:text-white">Matriks saat ini</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Rumusan awal dan penyempurnaan</p>
                            </div>
                            <x-ui.button size="sm" variant="outline" icon="file-earmark-pdf" :href="route('naskah.cetak', [$naskah, 'matriks-pdf'])" target="_blank" rel="noopener">PDF</x-ui.button>
                        </li>
                    </ul>
                </section>
            @endif
        @else
            <div class="flex flex-col items-center gap-3 py-16 text-slate-500">
                <x-ui.spinner class="size-8 text-primary-500" />
                <p class="text-sm">Sedang memuat data, harap tunggu...</p>
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="close()">Tutup</x-ui.button>
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
