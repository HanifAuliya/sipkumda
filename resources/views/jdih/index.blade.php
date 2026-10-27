{{-- Hasil pencarian JDIH. Data dari JdihController@index. --}}
@php
    // Tautan dengan satu filter diubah, filter lain dipertahankan.
    $dengan = fn (array $ubah) => route('jdih.index', array_filter(
        array_merge(['q' => $q, 'jenis' => $jenis, 'tahun' => $tahun, 'urut' => $urut === 'terbaru' ? null : $urut], $ubah),
        fn ($v) => $v !== null && $v !== '',
    ));

    $judul = match (true) {
        $q !== '' => "Hasil pencarian “{$q}”",
        $jenis && $tahun => "{$jenis} tahun {$tahun}",
        (bool) $jenis => $jenis,
        (bool) $tahun => "Produk hukum tahun {$tahun}",
        default => 'Semua produk hukum',
    };

    $filterAktif = array_filter([
        'q' => $q !== '' ? "“{$q}”" : null,
        'jenis' => $jenis,
        'tahun' => $tahun,
    ]);
@endphp

<x-publik-layout :title="$judul" :description="'Daftar ' . mb_strtolower($judul) . ' — JDIH Kabupaten Hulu Sungai Tengah.'">
    @if ($hasil->currentPage() > 1 || $q !== '')
        <x-slot:head><meta name="robots" content="noindex, follow"></x-slot:head>
    @endif

    {{-- Kepala halaman --}}
    <section class="bg-navy-900 pt-8 pb-16 text-white">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm text-white/60">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('beranda') }}" class="hover:text-white">Beranda</a></li>
                    <li aria-hidden="true"><i class="bi bi-chevron-right text-xs"></i></li>
                    <li class="text-white" aria-current="page">Produk Hukum</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">{{ $judul }}</h1>
            <x-jdih.form-cari :opsi="$opsi" :q="$q" :jenis="$jenis" :tahun="$tahun" :urut="$urut === 'terbaru' || $urut === 'relevan' ? null : $urut" class="mt-6" />
        </div>
    </section>

    <div class="mx-auto -mt-8 grid max-w-7xl gap-8 px-5 pb-20 lg:grid-cols-[16rem_minmax(0,1fr)] lg:px-8">
        {{-- Filter --}}
        <aside class="space-y-6 lg:pt-16">
            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                <h2 class="text-sm font-bold text-navy-900 dark:text-white">Jenis</h2>
                <ul class="mt-3 space-y-1 text-sm">
                    <li><a href="{{ $dengan(['jenis' => null]) }}" @class(['block rounded-lg px-3 py-2 transition hover:bg-slate-50 dark:hover:bg-navy-800', 'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => ! $jenis])>Semua jenis</a></li>
                    @foreach ($opsi['jenis'] as $item)
                        <li><a href="{{ $dengan(['jenis' => $item]) }}" @class(['block rounded-lg px-3 py-2 transition hover:bg-slate-50 dark:hover:bg-navy-800', 'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $jenis === $item])>{{ $item }}</a></li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl bg-white p-5 ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                <h2 class="text-sm font-bold text-navy-900 dark:text-white">Tahun</h2>
                <div class="mt-3 flex flex-wrap gap-1.5 text-sm">
                    <a href="{{ $dengan(['tahun' => null]) }}" @class(['rounded-lg px-3 py-1.5 ring-1 transition', 'bg-navy-900 text-white ring-navy-900 dark:bg-primary-500 dark:ring-primary-500' => ! $tahun, 'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $tahun])>Semua</a>
                    @foreach ($opsi['tahun'] as $item)
                        <a href="{{ $dengan(['tahun' => $item]) }}" @class(['rounded-lg px-3 py-1.5 ring-1 transition', 'bg-navy-900 text-white ring-navy-900 dark:bg-primary-500 dark:ring-primary-500' => (int) $tahun === (int) $item, 'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => (int) $tahun !== (int) $item])>{{ $item }}</a>
                    @endforeach
                </div>
            </section>
        </aside>

        {{-- Hasil --}}
        <section aria-labelledby="judul-hasil" class="min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-5 py-4 shadow-card ring-1 ring-slate-100 dark:bg-navy-900 dark:ring-navy-800">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 id="judul-hasil" class="text-sm text-slate-500 dark:text-slate-400">
                        <strong class="text-lg text-navy-900 dark:text-white">{{ number_format($hasil->total(), 0, ',', '.') }}</strong> produk hukum
                    </h2>
                    @foreach ($filterAktif as $kunci => $label)
                        <a href="{{ $dengan([$kunci => null]) }}" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-600 dark:bg-navy-800 dark:text-slate-200"
                            title="Hapus filter">
                            {{ $label }} <i class="bi bi-x-lg text-[0.65rem]"></i>
                        </a>
                    @endforeach
                </div>

                <form action="{{ route('jdih.index') }}" method="GET" class="flex items-center gap-2 text-sm">
                    @foreach (['q' => $q, 'jenis' => $jenis, 'tahun' => $tahun] as $nama => $nilai)
                        @if ($nilai !== null && $nilai !== '')<input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">@endif
                    @endforeach
                    <label for="urut" class="text-slate-500">Urutkan</label>
                    <select id="urut" name="urut" x-data x-on:change="$el.form.submit()"
                        class="rounded-lg border-0 bg-slate-50 py-2 pr-8 pl-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700">
                        @foreach (App\Support\Jdih::URUTAN as $nilai => $label)
                            @continue($nilai === 'relevan' && $q === '')
                            <option value="{{ $nilai }}" @selected($urut === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="rounded-lg bg-slate-100 px-3 py-2">Terapkan</button></noscript>
                </form>
            </div>

            @if ($hasil->isEmpty())
                <div class="mt-6 rounded-2xl bg-white px-6 py-14 text-center ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-slate-400 dark:bg-navy-800"><i class="bi bi-search"></i></span>
                    <h3 class="mt-4 text-lg font-semibold text-navy-900 dark:text-white">Produk hukum tidak ditemukan</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                        Coba kata kunci lain yang lebih umum, periksa ejaan, atau cari berdasarkan nomor seperti <strong>12/2025</strong>.
                    </p>
                    @if ($filterAktif)
                        <a href="{{ route('jdih.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-navy-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-navy-800">
                            <i class="bi bi-arrow-counterclockwise"></i> Hapus semua filter
                        </a>
                    @endif
                </div>
            @else
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach ($hasil as $dokumen)
                        <x-jdih.kartu :dokumen="$dokumen" />
                    @endforeach
                </div>

                <div class="mt-8">{{ $hasil->onEachSide(1)->links('jdih.paginasi') }}</div>
            @endif
        </section>
    </div>
</x-publik-layout>
