{{-- Daftar & pencarian JDIH. Data dari JdihController@index. --}}
@php
    // Tautan dengan satu filter diubah, filter lain dipertahankan.
    $dengan = fn (array $ubah) => route('jdih.index', array_filter(
        array_merge(['q' => $q, 'jenis' => $jenis, 'tahun' => $tahun, 'urut' => in_array($urut, ['terbaru', 'relevan']) ? null : $urut], $ubah),
        fn ($v) => $v !== null && $v !== '',
    ));

    $judul = match (true) {
        $q !== '' => "Hasil pencarian “{$q}”",
        $jenis && $tahun => "{$jenis} tahun {$tahun}",
        (bool) $jenis => $jenis,
        (bool) $tahun => "Produk hukum tahun {$tahun}",
        default => 'Produk Hukum Daerah',
    };

    $filterAktif = array_filter(['q' => $q !== '' ? "“{$q}”" : null, 'jenis' => $jenis, 'tahun' => $tahun]);
    $jumlahFilter = count(array_filter([$jenis, $tahun]));

    $pilihan = 'flex items-center justify-between rounded-lg px-3 py-2 text-sm transition';
    $aktif = 'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300';
    $biasa = 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-navy-800';
@endphp

<x-publik-layout :title="$judul" :description="'Daftar ' . mb_strtolower($judul) . ' Kabupaten Hulu Sungai Tengah — JDIH SIFKUMDA.'">
    @if ($hasil->currentPage() > 1 || $q !== '')
        <x-slot:head><meta name="robots" content="noindex, follow"></x-slot:head>
    @endif

    {{-- Kepala halaman --}}
    <section class="relative overflow-hidden bg-navy-900">
        <div class="pointer-events-none absolute -top-24 right-0 size-80 rounded-full bg-primary-500/20 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-5 pt-8 pb-10 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm text-white/60">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('beranda') }}" class="hover:text-white">Beranda</a></li>
                    <li aria-hidden="true"><i class="bi bi-chevron-right text-xs"></i></li>
                    <li class="text-white" aria-current="page">Produk Hukum</li>
                </ol>
            </nav>

            <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 text-xs font-bold tracking-wider text-brand-500 uppercase">
                        <i class="bi bi-journal-bookmark"></i> JDIH · Jaringan Dokumentasi dan Informasi Hukum
                    </p>
                    <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $judul }}</h1>
                    <p class="mt-2 text-sm text-white/70">Peraturan Bupati dan Keputusan Bupati Kabupaten Hulu Sungai Tengah yang telah ditetapkan.</p>
                </div>
                <x-jdih.form-cari :opsi="$opsi" :q="$q" :jenis="$jenis" :tahun="$tahun" :filter="false"
                    :urut="in_array($urut, ['terbaru', 'relevan']) ? null : $urut" class="lg:max-w-md" />
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl items-start gap-8 px-5 py-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:px-8">
        {{-- Filter: panel lipat di layar kecil, sidebar tetap di layar lebar --}}
        <aside x-data="{ buka: false }" class="lg:sticky lg:top-24">
            <button type="button" x-on:click="buka = !buka" x-bind:aria-expanded="buka"
                class="flex w-full items-center justify-between rounded-xl bg-white px-4 py-3 text-sm font-semibold text-navy-900 ring-1 ring-slate-200 lg:hidden dark:bg-navy-900 dark:text-white dark:ring-navy-800">
                <span class="inline-flex items-center gap-2"><i class="bi bi-funnel"></i> Filter
                    @if ($jumlahFilter)<span class="rounded-full bg-primary-500 px-2 text-xs text-white">{{ $jumlahFilter }}</span>@endif
                </span>
                <i class="bi bi-chevron-down transition" x-bind:class="buka && 'rotate-180'"></i>
            </button>

            {{-- Tersembunyi di layar kecil sampai tombol Filter dibuka; selalu tampil di layar lebar. --}}
            <div class="hidden lg:block" x-bind:class="{ 'block!': buka }">
                <div class="mt-3 divide-y divide-slate-100 rounded-2xl bg-white ring-1 ring-slate-200/70 lg:mt-0 dark:divide-navy-800 dark:bg-navy-900 dark:ring-navy-800">
                    <section class="p-4">
                        <h2 class="px-3 text-xs font-bold tracking-wider text-slate-400 uppercase">Jenis</h2>
                        <ul class="mt-2 space-y-0.5">
                            <li><a href="{{ $dengan(['jenis' => null]) }}" @class([$pilihan, $jenis ? $biasa : $aktif])>Semua jenis</a></li>
                            @foreach ($opsi['jenis'] as $item)
                                <li><a href="{{ $dengan(['jenis' => $item]) }}" @class([$pilihan, $jenis === $item ? $aktif : $biasa])>{{ $item }}</a></li>
                            @endforeach
                        </ul>
                    </section>

                    <section class="p-4">
                        <h2 class="px-3 text-xs font-bold tracking-wider text-slate-400 uppercase">Tahun</h2>
                        <div class="mt-3 grid grid-cols-3 gap-1.5 px-1 text-sm">
                            <a href="{{ $dengan(['tahun' => null]) }}"
                                @class(['rounded-lg py-1.5 text-center ring-1 transition', 'bg-navy-900 text-white ring-navy-900 dark:bg-primary-500 dark:ring-primary-500' => ! $tahun, 'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $tahun])>Semua</a>
                            @foreach ($opsi['tahun'] as $item)
                                <a href="{{ $dengan(['tahun' => $item]) }}"
                                    @class(['rounded-lg py-1.5 text-center ring-1 transition', 'bg-navy-900 text-white ring-navy-900 dark:bg-primary-500 dark:ring-primary-500' => (int) $tahun === (int) $item, 'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => (int) $tahun !== (int) $item])>{{ $item }}</a>
                            @endforeach
                        </div>
                    </section>

                    @if ($filterAktif)
                        <div class="p-4">
                            <a href="{{ route('jdih.index') }}" class="inline-flex items-center gap-2 px-3 text-sm font-semibold text-rose-600 hover:underline dark:text-rose-400">
                                <i class="bi bi-x-circle"></i> Hapus semua filter
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </aside>

        {{-- Hasil --}}
        <section aria-labelledby="judul-hasil" class="min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-navy-800">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <h2 id="judul-hasil" class="text-sm text-slate-500 dark:text-slate-400">
                        <strong class="text-base text-navy-900 dark:text-white">{{ number_format($hasil->total(), 0, ',', '.') }}</strong> produk hukum
                    </h2>
                    @foreach ($filterAktif as $kunci => $label)
                        <a href="{{ $dengan([$kunci => null]) }}" title="Hapus filter"
                            class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 transition hover:bg-rose-50 hover:text-rose-600 dark:bg-navy-800 dark:text-slate-200">
                            <span class="truncate">{{ $label }}</span> <i class="bi bi-x-lg text-[0.6rem]"></i>
                        </a>
                    @endforeach
                </div>

                <form action="{{ route('jdih.index') }}" method="GET" class="flex items-center gap-2 text-sm">
                    @foreach (['q' => $q, 'jenis' => $jenis, 'tahun' => $tahun] as $nama => $nilai)
                        @if ($nilai !== null && $nilai !== '')<input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">@endif
                    @endforeach
                    <label for="urut" class="text-slate-500 dark:text-slate-400">Urutkan</label>
                    <select id="urut" name="urut" x-data x-on:change="$el.form.submit()"
                        class="rounded-lg border-0 bg-white py-2 pr-8 pl-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-900 dark:ring-navy-700">
                        @foreach (App\Support\Jdih::URUTAN as $nilai => $label)
                            @continue($nilai === 'relevan' && $q === '')
                            <option value="{{ $nilai }}" @selected($urut === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="rounded-lg bg-slate-100 px-3 py-2">Terapkan</button></noscript>
                </form>
            </div>

            @if ($hasil->isEmpty())
                <div class="mt-6 rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center dark:border-navy-700">
                    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-slate-400 dark:bg-navy-800"><i class="bi bi-search"></i></span>
                    <h3 class="mt-4 text-lg font-semibold text-navy-900 dark:text-white">Produk hukum tidak ditemukan</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                        Coba kata kunci yang lebih umum, periksa ejaan, atau cari berdasarkan nomor seperti <strong>12/2025</strong>.
                    </p>
                    @if ($filterAktif)
                        <a href="{{ route('jdih.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-navy-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-navy-800">
                            <i class="bi bi-arrow-counterclockwise"></i> Hapus semua filter
                        </a>
                    @endif
                </div>
            @else
                <div class="mt-6 space-y-3">
                    @foreach ($hasil as $dokumen)
                        <x-jdih.baris :dokumen="$dokumen" />
                    @endforeach
                </div>

                <div class="mt-8">{{ $hasil->onEachSide(1)->links('jdih.paginasi') }}</div>
            @endif
        </section>
    </div>
</x-publik-layout>
