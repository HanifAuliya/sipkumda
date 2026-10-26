@section('title', 'Dashboard')

@php
    $user = auth()->user();

    // Warna per jenis, konsisten di semua grafik.
    $jenisColors = ['Peraturan Bupati' => '#5e72e4', 'Surat Keputusan' => '#fbb140'];

    $pieConfig = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $chartData['pie']['labels'],
            'datasets' => [[
                'data' => $chartData['pie']['data'],
                'backgroundColor' => collect($chartData['pie']['labels'])->map(fn ($label) => $jenisColors[$label] ?? '#94a3b8')->all(),
                'borderWidth' => 0,
                'hoverOffset' => 6,
            ]],
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'cutout' => '62%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'padding' => 16]]],
        ],
    ];

    $barConfig = [
        'type' => 'bar',
        'data' => [
            'labels' => $chartData['labels'],
            'datasets' => [
                ['label' => 'Peraturan Bupati', 'data' => $chartData['rancangan_bupati'], 'backgroundColor' => $jenisColors['Peraturan Bupati'], 'borderRadius' => 4],
                ['label' => 'Surat Keputusan', 'data' => $chartData['keputusan_bupati'], 'backgroundColor' => $jenisColors['Surat Keputusan'], 'borderRadius' => 4],
            ],
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'padding' => 16]]],
            'scales' => [
                'x' => ['stacked' => true, 'grid' => ['display' => false]],
                'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ],
    ];

    $lineSeries = [
        ['Rancangan Disetujui', 'rancangan', '#5e72e4'],
        ['Revisi Selesai', 'revisi', '#fb6340'],
        ['Fasilitasi Divalidasi', 'fasilitasi', '#2dce89'],
        ['Dokumentasi Arsip', 'dokumentasi', '#8965e0'],
    ];

    $lineConfig = [
        'type' => 'line',
        'data' => [
            'labels' => $lineChartData['labels'],
            'datasets' => collect($lineSeries)->map(fn ($s) => [
                'label' => $s[0],
                'data' => $lineChartData[$s[1]],
                'borderColor' => $s[2],
                'backgroundColor' => $s[2],
                'tension' => 0.35,
                'pointRadius' => 3,
                'borderWidth' => 2.5,
            ])->all(),
        ],
        'options' => [
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'padding' => 16]]],
            'scales' => [
                'x' => ['grid' => ['display' => false]],
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ],
    ];
@endphp

<div class="space-y-6">
    {{-- Sambutan --}}
    <section class="relative overflow-hidden rounded-2xl bg-linear-to-br from-navy-900 via-navy-800 to-primary-700 p-6 text-white shadow-card sm:p-8">
        <div class="pointer-events-none absolute -top-16 -right-10 size-64 rounded-full bg-white/5"></div>
        <div class="pointer-events-none absolute -right-24 -bottom-24 size-72 rounded-full bg-primary-400/10"></div>

        <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ asset('assets/img/theme/profile.png') }}" alt="" class="size-16 rounded-full ring-4 ring-white/15">
                <div class="min-w-0">
                    <p class="text-sm text-white/70">Selamat datang kembali,</p>
                    <h1 class="truncate text-xl font-bold text-white sm:text-2xl">{{ $user->nama_user }}</h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 font-semibold">{{ $user->getRoleNames()->first() ?? 'Tanpa Role' }}</span>
                        <span class="text-white/70">NIP {{ $user->NIP }}</span>
                        @if ($user->perangkatDaerah)
                            <span class="text-white/70">· {{ $user->perangkatDaerah->nama_perangkat_daerah }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if ($panduanUrl)
                <a href="{{ $panduanUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-3 self-start rounded-xl bg-white/10 px-4 py-3 ring-1 ring-white/15 transition hover:bg-white/15 md:self-auto">
                    <span class="grid size-10 place-items-center rounded-lg bg-white text-lg text-navy-900">
                        <i class="bi bi-journal-text"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold">Manual Pengguna</span>
                        <span class="block text-xs text-white/70">Baca panduan sesuai peran Anda</span>
                    </span>
                    <i class="bi bi-arrow-up-right ml-1 text-white/60"></i>
                </a>
            @endif
        </div>
    </section>

    {{-- Statistik --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Rancangan Produk Hukum" :value="$jumlahRancangan" suffix="data" icon="clipboard2-data" color="rose" />
        <x-ui.stat-card label="Revisi Rancangan" :value="$jumlahRevisi" suffix="data" icon="pencil-square" color="orange" />
        <x-ui.stat-card label="Fasilitasi Produk Hukum" :value="$jumlahFasilitasi" suffix="data" icon="file-text" color="emerald" />
        <x-ui.stat-card label="Arsip Produk Hukum" :value="$jumlahDokumentasi" suffix="data" icon="folder2-open" color="sky" />
    </div>

    {{-- Grafik --}}
    <div class="grid gap-6 lg:grid-cols-5">
        <x-ui.card title="Total Rancangan" description="Berdasarkan jenis" class="lg:col-span-2">
            <div x-data="chart(@js($pieConfig))" class="h-72">
                <canvas x-ref="canvas" aria-label="Grafik total rancangan per jenis" role="img"></canvas>
            </div>
        </x-ui.card>

        <x-ui.card title="Pengajuan Rancangan" description="Per bulan, tahun {{ now()->year }}" class="lg:col-span-3">
            <div x-data="chart(@js($barConfig))" class="h-72">
                <canvas x-ref="canvas" aria-label="Grafik pengajuan rancangan per bulan" role="img"></canvas>
            </div>
        </x-ui.card>
    </div>

    <x-ui.card title="Lama Proses Rancangan" description="Rata-rata durasi tiap tahap (hari), tahun {{ $selectedYear }}">
        <div x-data="chart(@js($lineConfig))" class="h-80">
            <canvas x-ref="canvas" aria-label="Grafik lama proses rancangan" role="img"></canvas>
        </div>
    </x-ui.card>
</div>
