@php
    use App\Enums\TahapNaskah;
    use App\Support\KalimatRiwayat;
    use App\Support\LaporanNaskah;
    use Illuminate\Support\Js;

    $warnaIkon = [
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300',
        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'danger' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
        'slate' => 'bg-slate-100 text-slate-500 dark:bg-navy-800 dark:text-slate-400',
    ];
    $bukaDetail = fn ($naskah) => 'Livewire.dispatch(\'bukaDetailNaskah\', { slug: ' . Js::from($naskah->slug) . ' })';
@endphp

<div class="space-y-6">
    <x-ui.page-header title="Laporan & Pemantauan" icon="bar-chart"
        description="Rekap naskah, sebaran per Perangkat Daerah, dan catatan kegiatan semua naskah.">
        <x-slot:actions>
            <x-ui.dropdown align="right" width="w-72">
                <x-slot:trigger>
                    <x-ui.button variant="outline" icon="download" loading="unduhExcel,unduhPdf" title="Sesuai periode dan filter yang sedang dipakai">
                        Unduh <i class="bi bi-chevron-down text-xs" aria-hidden="true"></i>
                    </x-ui.button>
                </x-slot:trigger>
                <x-ui.dropdown-item icon="file-earmark-pdf" wire:click="unduhPdf" description="Ringkasan, sebaran per PD, dan naskah tertahan, berkop untuk pimpinan">PDF laporan</x-ui.dropdown-item>
                <x-ui.dropdown-item icon="file-earmark-spreadsheet" wire:click="unduhExcel" description="Sheet Ringkasan, Per Perangkat Daerah, Daftar Naskah, dan Aktivitas">Excel lengkap</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tabs>
        <x-ui.tab :active="$tab === 'ringkasan'" icon="bar-chart" wire:click="$set('tab', 'ringkasan')">Ringkasan</x-ui.tab>
        <x-ui.tab :active="$tab === 'perangkat'" icon="building" wire:click="$set('tab', 'perangkat')"><span class="sm:hidden">Per PD</span><span class="max-sm:hidden">Per Perangkat Daerah</span></x-ui.tab>
        <x-ui.tab :active="$tab === 'aktivitas'" icon="activity" wire:click="$set('tab', 'aktivitas')">Aktivitas</x-ui.tab>
    </x-ui.tabs>

    {{-- Filter bersama untuk ketiga tab, Unduh Excel, dan Unduh PDF. --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        @if ($periode !== 'rentang')
            <x-form.select wire:model.live="tahun" icon="calendar3" class="sm:w-40" aria-label="Tahun">
                <option value="">Semua tahun</option>
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </x-form.select>
        @endif
        <x-form.select wire:model.live="periode" icon="calendar-event" class="sm:w-48" aria-label="Periode">
            <option value="">Setahun penuh</option>
            <optgroup label="Semester">
                <option value="s1">Semester I</option>
                <option value="s2">Semester II</option>
            </optgroup>
            <optgroup label="Triwulan">
                @foreach (['t1', 't2', 't3', 't4'] as $k)
                    <option value="{{ $k }}">{{ \App\Support\PeriodeLaporan::PILIHAN[$k] }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Bulan">
                @foreach (range(1, 12) as $b)
                    <option value="b{{ $b }}">{{ \App\Support\PeriodeLaporan::PILIHAN['b' . $b] }}</option>
                @endforeach
            </optgroup>
            <option value="rentang">Rentang tanggal…</option>
        </x-form.select>
        @if ($periode === 'rentang')
            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 sm:flex">
                <x-form.input type="date" wire:model.live="dari" class="min-w-0 sm:w-40" aria-label="Dari tanggal" />
                <span class="text-sm text-slate-400">s.d.</span>
                <x-form.input type="date" wire:model.live="sampai" class="min-w-0 sm:w-40" aria-label="Sampai tanggal" />
            </div>
        @endif
        <x-form.select wire:model.live="jenis" icon="tags" class="sm:w-52" aria-label="Jenis produk hukum">
            <option value="">Semua jenis</option>
            <option value="Peraturan Bupati">Peraturan Bupati</option>
            <option value="Keputusan Bupati">Keputusan Bupati</option>
        </x-form.select>
        <x-form.select wire:model.live="pd" icon="building" class="sm:w-72" aria-label="Perangkat Daerah">
            <option value="">Semua Perangkat Daerah</option>
            @foreach ($daftarPd as $p)
                <option value="{{ $p->id }}">{{ $p->nama_perangkat_daerah }}</option>
            @endforeach
        </x-form.select>
        @if ($tab === 'aktivitas')
            <x-form.select wire:model.live="peran" icon="person-badge" class="sm:w-48" aria-label="Peran pelaku">
                <option value="">Semua peran</option>
                @foreach ($peranList as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
            </x-form.select>
        @endif
    </div>

    <p class="-mt-3 flex flex-wrap items-center gap-x-2 text-sm text-slate-500 dark:text-slate-400">
        <i class="bi bi-funnel" aria-hidden="true"></i>
        Menampilkan <strong class="font-semibold text-slate-700 dark:text-slate-200">{{ $labelPeriode }}</strong>
        · {{ in_array($jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true) ? $jenis : 'semua jenis' }}
        · {{ $daftarPd->firstWhere('id', (int) $pd)?->nama_perangkat_daerah ?? 'semua Perangkat Daerah' }}
        @if ($tab !== 'aktivitas')
            <span class="text-slate-400">(naskah menurut tanggal diajukan, produk hukum menurut tanggal penetapan)</span>
        @endif
    </p>

    @if ($tab === 'ringkasan')
        @php
            $tekan = 'cursor-pointer hover:-translate-y-0.5 hover:shadow-lg';
            $jenisAktif = in_array($jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true) ? [$jenis] : ['Peraturan Bupati', 'Keputusan Bupati'];
            // Palet tervalidasi (skrip validator dataviz) untuk permukaan kartu terang dan gelap.
            $warnaJenis = ['Peraturan Bupati' => ['terang' => '#5e72e4', 'gelap' => '#7083e8'], 'Keputusan Bupati' => ['terang' => '#d97706', 'gelap' => '#d97706']];
            $grafikBatang = function (array $perJenis) use ($grafik, $jenisAktif, $warnaJenis) {
                $ds = [];
                foreach ($jenisAktif as $i => $j) {
                    $ds[] = [
                        'label' => $j, 'data' => $perJenis[$j], 'warna' => $warnaJenis[$j], 'celah' => true, 'stack' => 'jenis',
                        'maxBarThickness' => 28, 'borderSkipped' => false,
                        'borderRadius' => $i === count($jenisAktif) - 1 ? ['topLeft' => 4, 'topRight' => 4] : 0,
                    ];
                }

                return [
                    'type' => 'bar',
                    'data' => ['labels' => $grafik['label'], 'datasets' => $ds],
                    'options' => [
                        'responsive' => true, 'maintainAspectRatio' => false,
                        'interaction' => ['mode' => 'index', 'intersect' => false],
                        'plugins' => ['legend' => ['display' => count($ds) > 1, 'position' => 'top', 'align' => 'end',
                            'labels' => ['boxWidth' => 10, 'boxHeight' => 10, 'usePointStyle' => true, 'pointStyle' => 'rectRounded']]],
                        'scales' => [
                            'x' => ['stacked' => true, 'grid' => ['display' => false], 'border' => ['display' => false], 'ticks' => ['maxRotation' => 0, 'autoSkipPadding' => 12]],
                            'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0], 'border' => ['display' => false]],
                        ],
                    ],
                ];
            };
            $gDiajukan = $grafikBatang($grafik['diajukan']);
            $gDitetapkan = $grafikBatang($grafik['ditetapkan']);
            $gLangkah = [
                'type' => 'bar',
                'data' => ['labels' => $grafik['langkah']['label'], 'datasets' => [[
                    'label' => 'Naskah', 'data' => $grafik['langkah']['jumlah'], 'warna' => $warnaJenis['Peraturan Bupati'],
                    'borderRadius' => 4, 'maxBarThickness' => 22,
                ]]],
                'options' => [
                    'indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false,
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => [
                        'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'border' => ['display' => false]],
                        'y' => ['grid' => ['display' => false], 'border' => ['display' => false]],
                    ],
                ],
            ];
            $hrefDitetapkan = route('dokumentasi.main', array_filter(['tahun' => $tahunPenuh, 'jenisRancangan' => count($jenisAktif) === 1 ? $jenisAktif[0] : null]));
        @endphp

        <div class="grid grid-cols-2 gap-3 transition-opacity sm:gap-5 lg:grid-cols-3 xl:grid-cols-5" wire:loading.class="opacity-60">
            <x-ui.stat-card rapat label="Diajukan" :value="$ringkasan['diajukan']" suffix="naskah" icon="send"
                wire:click="$set('tab', 'perangkat')" :class="$tekan" title="Lihat sebaran per Perangkat Daerah">
                <p class="mt-1 text-sm text-slate-500 max-sm:pr-9 dark:text-slate-400">{{ $ringkasan['peraturan'] }} Perbup · {{ $ringkasan['keputusan'] }} Kepbup</p>
            </x-ui.stat-card>
            <x-ui.stat-card rapat label="Sedang berjalan" :value="$ringkasan['berjalan']" suffix="naskah" icon="hourglass-split" color="orange"
                x-on:click="document.getElementById('per-tahap')?.scrollIntoView({ behavior: 'smooth', block: 'start' })" :class="$tekan" title="Lihat per tahap" />
            <x-ui.stat-card rapat label="Selesai" :value="$ringkasan['selesai']" suffix="naskah" icon="check2-circle" color="emerald">
                <p class="mt-1 text-sm text-slate-500 max-sm:pr-9 dark:text-slate-400">Sudah diarsipkan</p>
            </x-ui.stat-card>
            <x-ui.stat-card rapat label="Ditetapkan" :value="$ditetapkan['total']" icon="folder-check" color="amber"
                :href="$hrefDitetapkan" title="Buka Dokumentasi">
                <p class="mt-1 text-sm text-slate-500 max-sm:pr-9 dark:text-slate-400">{{ $ditetapkan['peraturan'] }} Perbup · {{ $ditetapkan['keputusan'] }} Kepbup</p>
            </x-ui.stat-card>
            <x-ui.stat-card rapat label="Rata-rata lama proses" :value="$ringkasan['rata_proses'] ?? '-'" :suffix="$ringkasan['rata_proses'] !== null ? 'hari' : null" icon="clock-history" color="sky">
                <p class="mt-1 text-sm text-slate-500 max-sm:pr-9 dark:text-slate-400">Diajukan sampai diarsipkan</p>
            </x-ui.stat-card>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-3">
            <x-ui.card class="xl:col-span-2" x-data="{ ukuran: 'diajukan' }"
                :title="($grafik['tahunan'] ? 'Per tahun' : 'Per bulan')"
                :description="$labelPeriode === 'Semua waktu' ? '12 bulan terakhir' : $labelPeriode">
                <x-slot:actions>
                    <div class="flex rounded-lg bg-slate-100 p-1 text-sm font-semibold dark:bg-navy-800" role="tablist" aria-label="Ukuran grafik">
                        <button type="button" role="tab" class="rounded-md px-3 py-1.5 transition" x-bind:aria-selected="ukuran === 'diajukan'"
                            x-bind:class="ukuran === 'diajukan' ? 'bg-white text-primary-700 shadow-soft dark:bg-navy-900 dark:text-white' : 'text-slate-500'" x-on:click="ukuran = 'diajukan'">Naskah diajukan</button>
                        <button type="button" role="tab" class="rounded-md px-3 py-1.5 transition" x-bind:aria-selected="ukuran === 'ditetapkan'"
                            x-bind:class="ukuran === 'ditetapkan' ? 'bg-white text-primary-700 shadow-soft dark:bg-navy-900 dark:text-white' : 'text-slate-500'" x-on:click="ukuran = 'ditetapkan'">Produk hukum ditetapkan</button>
                    </div>
                </x-slot:actions>

                <div x-show="ukuran === 'diajukan'" class="h-64" wire:key="g-diajukan-{{ md5(json_encode($gDiajukan)) }}" wire:ignore x-data="chart(@js($gDiajukan))"
                    role="img" aria-label="Grafik naskah diajukan per {{ $grafik['tahunan'] ? 'tahun' : 'bulan' }}"><canvas x-ref="canvas"></canvas></div>
                <div x-show="ukuran === 'ditetapkan'" x-cloak class="h-64" wire:key="g-ditetapkan-{{ md5(json_encode($gDitetapkan)) }}" wire:ignore x-data="chart(@js($gDitetapkan))"
                    role="img" aria-label="Grafik produk hukum ditetapkan per {{ $grafik['tahunan'] ? 'tahun' : 'bulan' }}"><canvas x-ref="canvas"></canvas></div>

                <details class="mt-3 text-sm">
                    <summary class="cursor-pointer font-semibold text-primary-600 dark:text-primary-300">Lihat sebagai tabel</summary>
                    <div class="mt-2 overflow-x-auto">
                        <table class="table-base min-w-full">
                            <thead>
                                <tr>
                                    <th>{{ $grafik['tahunan'] ? 'Tahun' : 'Bulan' }}</th>
                                    @foreach ($jenisAktif as $j)
                                        <th class="text-right">Diajukan {{ $j === 'Peraturan Bupati' ? 'Perbup' : 'Kepbup' }}</th>
                                    @endforeach
                                    @foreach ($jenisAktif as $j)
                                        <th class="text-right">Ditetapkan {{ $j === 'Peraturan Bupati' ? 'Perbup' : 'Kepbup' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grafik['label'] as $i => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        @foreach ($jenisAktif as $j)
                                            <td class="text-right">{{ $grafik['diajukan'][$j][$i] }}</td>
                                        @endforeach
                                        @foreach ($jenisAktif as $j)
                                            <td class="text-right">{{ $grafik['ditetapkan'][$j][$i] }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </x-ui.card>

            <x-ui.card title="Posisi naskah yang berjalan" description="Di langkah mana naskah sedang menumpuk.">
                <div class="h-64" wire:key="g-langkah-{{ md5(json_encode($gLangkah)) }}" wire:ignore x-data="chart(@js($gLangkah))"
                    role="img" aria-label="Grafik naskah yang sedang berjalan per langkah: {{ collect($grafik['langkah']['label'])->map(fn ($l, $i) => $l . ' ' . $grafik['langkah']['jumlah'][$i])->join(', ') }}">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card :title="'Tertahan lebih dari ' . $batas . ' hari'" description="Naskah yang belum berpindah tahap. Pemegang gilirannya diingatkan otomatis setiap {{ $batas }} hari.">
            <x-ui.table>
                <x-slot:head>
                    <th>Naskah</th>
                    <th class="max-sm:hidden">Tahap</th>
                    <th class="max-sm:hidden">Sejak</th>
                    <th class="text-right">Lama</th>
                </x-slot:head>
                @forelse ($tertahan as $b)
                    <tr wire:key="tertahan-{{ $b['naskah']->id }}" class="cursor-pointer" x-on:click="{{ $bukaDetail($b['naskah']) }}">
                        <td>
                            <p class="sel-utama">{{ $b['naskah']->nomor_tampil }}</p>
                            <p class="sel-judul mt-0.5">{{ $b['naskah']->tentang }}</p>
                            <x-ui.badge :color="$b['naskah']->tahap->warna()" class="mt-1 sm:hidden">{{ $b['naskah']->tahap->label() }}</x-ui.badge>
                        </td>
                        <td class="whitespace-nowrap max-sm:hidden">
                            <x-ui.badge :color="$b['naskah']->tahap->warna()">{{ $b['naskah']->tahap->label() }}</x-ui.badge>
                            <p class="sel-kedua mt-1">Giliran {{ $b['naskah']->tahap->peran() }}</p>
                        </td>
                        <td class="sel-kedua whitespace-nowrap max-sm:hidden">{{ LaporanNaskah::formatTanggal($b['masuk']) }}</td>
                        <td class="text-right font-semibold whitespace-nowrap text-orange-600 dark:text-orange-300">{{ $b['lama_tahap'] }} hari</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="4" icon="check2-circle" message="Tidak ada naskah yang tertahan." />
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <x-ui.card id="per-tahap" class="scroll-mt-24" title="Per Tahap" description="Jumlah naskah di tiap tahap sekarang, dan rata-rata lamanya naskah berada di tahap itu. Tekan barisnya untuk melihat naskahnya.">
                <x-ui.table x-data="{ buka: null }">
                    <x-slot:head>
                        <th>Tahap</th>
                        <th class="text-right">Sekarang</th>
                        <th class="text-right">Rata-rata</th>
                    </x-slot:head>
                    @foreach ($perTahap as $i => $t)
                        <tr wire:key="tahap-{{ $t['tahap']->value }}" @class(['cursor-pointer' => $t['sekarang'] > 0])
                            @if ($t['sekarang'] > 0) x-on:click="buka = buka === {{ $i }} ? null : {{ $i }}" @endif>
                            <td>
                                <span class="flex items-center gap-2">
                                    @if ($t['sekarang'] > 0)
                                        <i class="bi bi-chevron-right text-xs text-slate-400 transition-transform" x-bind:class="buka === {{ $i }} && 'rotate-90'" aria-hidden="true"></i>
                                    @else
                                        <span class="w-3"></span>
                                    @endif
                                    <span>{{ $t['tahap']->label() }} <span class="sel-kedua">· {{ $t['tahap']->peran() }}</span></span>
                                </span>
                            </td>
                            <td class="text-right font-semibold">{{ $t['sekarang'] }}</td>
                            <td class="text-right whitespace-nowrap text-slate-500 dark:text-slate-400">@if ($t['rata_hari'] === null) - @elseif ($t['rata_hari'] < 1) &lt; 1 hari @else {{ str_replace('.', ',', $t['rata_hari']) }} hari @endif</td>
                        </tr>
                        @if ($t['sekarang'] > 0)
                            <tr wire:key="tahap-isi-{{ $t['tahap']->value }}" x-show="buka === {{ $i }}" x-cloak>
                                <td colspan="3" class="bg-slate-50/70 dark:bg-navy-950/40">
                                    <ul class="divide-y divide-slate-100 dark:divide-navy-800">
                                        @foreach ($t['naskah'] as $n)
                                            <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2">
                                                <span class="font-semibold text-navy-900 dark:text-white">{{ $n->nomor_tampil }}</span>
                                                <span class="min-w-0 flex-1 text-slate-600 dark:text-slate-300">{{ $n->tentang }}</span>
                                                <x-ui.button variant="outline" size="sm" icon="eye" x-on:click.stop="{{ $bukaDetail($n) }}">Detail</x-ui.button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </x-ui.table>
            </x-ui.card>

            <x-ui.card title="Per Perangkat Daerah">
                <x-slot:actions>
                    <x-ui.button variant="ghost" size="sm" icon="arrow-right" wire:click="$set('tab', 'perangkat')">Lihat sebaran</x-ui.button>
                </x-slot:actions>
                <x-ui.table>
                    <x-slot:head>
                        <th>Perangkat Daerah</th>
                        <th class="text-right">Diajukan</th>
                        <th class="text-right">Berjalan</th>
                        <th class="text-right">Selesai</th>
                    </x-slot:head>
                    @forelse ($perPd as $p)
                        <tr wire:key="pd-{{ $loop->index }}">
                            <td><p class="sel-judul">{{ $p['nama'] }}</p></td>
                            <td class="text-right font-semibold">{{ $p['diajukan'] }}</td>
                            <td class="text-right">{{ $p['berjalan'] }}</td>
                            <td class="text-right">{{ $p['selesai'] }}</td>
                        </tr>
                    @empty
                        <x-ui.empty-row colspan="4" message="Belum ada naskah yang diajukan." />
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>
    @elseif ($tab === 'perangkat')
        <x-ui.card title="Sebaran per Perangkat Daerah"
            description="Jumlah naskah tiap Perangkat Daerah menurut langkahnya sekarang. Tekan barisnya untuk melihat naskahnya.">
            <x-slot:actions>
                @unless ($pd)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model.live="semuaPd" class="rounded text-primary-600 focus:ring-primary-300">
                        Tampilkan yang belum mengajukan
                    </label>
                @endunless
            </x-slot:actions>

            <x-ui.table x-data="{ buka: null }">
                <x-slot:head>
                    <th>Perangkat Daerah</th>
                    @foreach (TahapNaskah::LANGKAH as $langkah)
                        <th class="text-center whitespace-nowrap">{{ $langkah }}</th>
                    @endforeach
                    <th class="text-right">Total</th>
                </x-slot:head>
                @forelse ($matriks as $i => $baris)
                    <tr wire:key="sebaran-{{ md5($baris['nama']) }}" @class(['cursor-pointer' => $baris['total'] > 0])
                        @if ($baris['total'] > 0) x-on:click="buka = buka === {{ $i }} ? null : {{ $i }}" @endif>
                        <td class="min-w-56">
                            <p class="sel-judul flex items-center gap-2">
                                @if ($baris['total'] > 0)
                                    <i class="bi bi-chevron-right text-xs text-slate-400 transition-transform" x-bind:class="buka === {{ $i }} && 'rotate-90'" aria-hidden="true"></i>
                                @else
                                    <span class="w-3"></span>
                                @endif
                                {{ $baris['nama'] }}
                            </p>
                        </td>
                        @foreach ($baris['jumlah'] as $j)
                            <td class="text-center {{ $j ? 'font-semibold text-navy-900 dark:text-white' : 'text-slate-300 dark:text-navy-600' }}">{{ $j ?: '–' }}</td>
                        @endforeach
                        <td class="text-right font-bold">{{ $baris['total'] }}</td>
                    </tr>
                    @if ($baris['total'] > 0)
                        <tr wire:key="sebaran-isi-{{ md5($baris['nama']) }}" x-show="buka === {{ $i }}" x-cloak>
                            <td colspan="{{ count(TahapNaskah::LANGKAH) + 2 }}" class="bg-slate-50/70 dark:bg-navy-950/40">
                                <ul class="divide-y divide-slate-100 dark:divide-navy-800">
                                    @foreach ($baris['naskah'] as $n)
                                        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2.5">
                                            <span class="font-semibold text-navy-900 dark:text-white">{{ $n->nomor_tampil }}</span>
                                            <x-ui.status-badge :value="$n->jenis" />
                                            <span class="min-w-0 flex-1 text-slate-600 dark:text-slate-300">{{ $n->tentang }}</span>
                                            <x-ui.badge :color="$n->tahap->warna()">{{ $n->tahap->label() }}</x-ui.badge>
                                            <x-ui.button variant="outline" size="sm" icon="eye" x-on:click.stop="{{ $bukaDetail($n) }}">Detail</x-ui.button>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                @empty
                    <x-ui.empty-row :colspan="count(TahapNaskah::LANGKAH) + 2" icon="building" message="Belum ada naskah yang diajukan dengan filter ini." />
                @endforelse
            </x-ui.table>
        </x-ui.card>
    @else
        <x-ui.card title="Aktivitas" description="Catatan kegiatan semua naskah, terbaru di atas.">
            <x-slot:actions>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model.live="rumusan" class="rounded text-primary-600 focus:ring-primary-300">
                    Termasuk perubahan rumusan
                </label>
            </x-slot:actions>

            <ol class="divide-y divide-slate-100 transition-opacity dark:divide-navy-800" wire:loading.class="opacity-60">
                @forelse ($aktivitas as $r)
                    @php $k = KalimatRiwayat::untuk($r); @endphp
                    <li wire:key="aktivitas-{{ $r->id }}" class="flex gap-3 py-3.5 first:pt-0">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $warnaIkon[$k['warna']] }}">
                            <i class="bi bi-{{ $k['ikon'] }}" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                                <p class="text-sm text-slate-700 dark:text-slate-200">
                                    <strong class="font-semibold text-navy-900 dark:text-white">{{ $r->user->nama_user ?? 'Sistem' }}</strong>
                                    @if ($r->user?->getRoleNames()->first())
                                        <span class="text-slate-400">({{ $r->user->getRoleNames()->first() }})</span>
                                    @endif
                                    {{ $k['teks'] }}
                                </p>
                                <time class="text-xs whitespace-nowrap text-slate-400" datetime="{{ $r->created_at->toIso8601String() }}">{{ $r->created_at->translatedFormat('d M Y, H.i') }}</time>
                            </div>
                            @if ($r->naskah)
                                <button type="button" class="mt-1 flex max-w-full items-center gap-2 text-left text-sm text-slate-500 transition hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-300"
                                    x-on:click="{{ $bukaDetail($r->naskah) }}">
                                    <span class="font-semibold whitespace-nowrap">{{ $r->naskah->nomor_tampil }}</span>
                                    <span class="truncate">{{ $r->naskah->tentang }}</span>
                                    <span class="whitespace-nowrap max-md:hidden">· {{ $r->naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</span>
                                </button>
                            @endif
                            @if ($r->catatan && ! in_array($r->aksi, ['tahapan_tanggal'], true))
                                <p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-navy-800 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($r->catatan, 240) }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li><x-ui.empty-state icon="activity" title="Belum ada aktivitas dengan filter ini." /></li>
                @endforelse
            </ol>

            <div class="mt-5">{{ $aktivitas->links() }}</div>
        </x-ui.card>
    @endif
</div>
