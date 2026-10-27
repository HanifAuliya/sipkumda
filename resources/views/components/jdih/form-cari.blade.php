{{--
    Form pencarian JDIH (GET ke /produk-hukum).
    <x-jdih.form-cari :opsi="$opsi" :q="$q" :jenis="$jenis" :tahun="$tahun" besar />
--}}
@props(['opsi', 'q' => '', 'jenis' => null, 'tahun' => null, 'urut' => null, 'besar' => false])

<form action="{{ route('jdih.index') }}" method="GET" role="search" {{ $attributes->class('w-full') }}>
    <div @class([
        'flex flex-col gap-2 rounded-2xl bg-white p-2 shadow-xl ring-1 ring-slate-200 sm:flex-row dark:bg-navy-900 dark:ring-navy-700',
        'sm:p-2.5' => $besar,
    ])>
        <label class="flex flex-1 items-center gap-3 px-3">
            <i @class(['bi bi-search text-slate-400', 'text-xl' => $besar])></i>
            <span class="sr-only">Kata kunci</span>
            <input type="search" name="q" value="{{ $q }}" maxlength="100" autocomplete="off"
                placeholder="Cari judul atau nomor, mis. sampah, 12/2025"
                @class([
                    'w-full border-0 bg-transparent p-0 text-slate-800 placeholder:text-slate-400 focus:ring-0 dark:text-white',
                    'py-3 text-base sm:text-lg' => $besar,
                    'py-2.5 text-sm' => ! $besar,
                ])>
        </label>

        <div class="flex gap-2">
            <label class="sr-only" for="filter-jenis">Jenis</label>
            <select id="filter-jenis" name="jenis"
                class="min-w-0 flex-1 rounded-xl border-0 bg-slate-50 py-2.5 pr-8 pl-3 text-sm text-slate-700 focus:ring-2 focus:ring-primary-300 sm:w-44 sm:flex-none dark:bg-navy-800 dark:text-slate-200">
                <option value="">Semua jenis</option>
                @foreach ($opsi['jenis'] as $item)
                    <option value="{{ $item }}" @selected($jenis === $item)>{{ $item }}</option>
                @endforeach
            </select>

            <label class="sr-only" for="filter-tahun">Tahun</label>
            <select id="filter-tahun" name="tahun"
                class="w-36 rounded-xl border-0 bg-slate-50 py-2.5 pr-8 pl-3 text-sm text-slate-700 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:text-slate-200">
                <option value="">Semua tahun</option>
                @foreach ($opsi['tahun'] as $item)
                    <option value="{{ $item }}" @selected((int) $tahun === (int) $item)>{{ $item }}</option>
                @endforeach
            </select>

            @if ($urut)
                <input type="hidden" name="urut" value="{{ $urut }}">
            @endif

            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-500 px-5 text-sm font-semibold text-white shadow-soft transition hover:bg-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300">
                <span class="hidden sm:inline">Cari</span><i class="bi bi-arrow-right sm:hidden"></i>
            </button>
        </div>
    </div>
</form>
