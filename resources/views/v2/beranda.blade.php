<div class="mx-auto max-w-6xl px-4 py-8 lg:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-navy-900 dark:text-white">Pekerjaan Anda</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Naskah yang sedang menunggu tindakan Anda ada di bagian atas.</p>
        </div>
        @if ($bisaBuat)
            <x-ui.button :href="route('v2.naskah.buat')" icon="plus-lg" wire:navigate>Buat naskah</x-ui.button>
        @endif
    </div>

    <section class="mt-6" aria-labelledby="judul-giliran">
        <h2 id="judul-giliran" class="text-xs font-bold tracking-wider text-slate-500 uppercase">
            Giliran Anda <span class="ml-1 rounded-full bg-brand-500/15 px-2 py-0.5 text-brand-600">{{ $giliranSaya->count() }}</span>
        </h2>

        @if ($giliranSaya->isEmpty())
            <p class="mt-3 rounded-xl border border-dashed border-slate-300 px-5 py-8 text-center text-sm text-slate-500 dark:border-navy-700">
                Tidak ada naskah yang menunggu Anda.
            </p>
        @else
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($giliranSaya as $naskah)
                    <a href="{{ route('v2.naskah.kerja', $naskah) }}" wire:navigate wire:key="giliran-{{ $naskah->id }}"
                        class="group rounded-xl border-l-4 border-brand-500 bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-card dark:bg-navy-900 dark:ring-navy-800">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold text-slate-500">{{ $naskah->nomor }}</span>
                            <x-ui.badge :color="$naskah->tahap->warna()">{{ $naskah->tahap->label() }}</x-ui.badge>
                        </div>
                        <h3 class="mt-2 line-clamp-2 font-semibold leading-snug text-navy-900 group-hover:text-primary-700 dark:text-white dark:group-hover:text-primary-300">
                            {{ $naskah->tentang }}
                        </h3>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ $naskah->jenis }} · {{ $naskah->baris_count }} baris · {{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user }}
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-10" aria-labelledby="judul-semua">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="judul-semua" class="text-xs font-bold tracking-wider text-slate-500 uppercase">Semua naskah</h2>
            <div class="flex flex-wrap items-center gap-2">
                <label>
                    <span class="sr-only">Tahap</span>
                    <select wire:model.live="tahap" class="rounded-lg border-0 bg-white py-2 pr-8 pl-3 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-900 dark:ring-navy-700">
                        <option value="">Semua tahap</option>
                        @foreach (App\Enums\TahapNaskah::cases() as $pilihan)
                            <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="relative">
                    <span class="sr-only">Cari naskah</span>
                    <i class="bi bi-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nomor atau judul…"
                        class="w-64 max-w-full rounded-lg border-0 bg-white py-2 pr-3 pl-9 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-900 dark:ring-navy-700">
                </label>
            </div>
        </div>

        <div class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/70 dark:divide-navy-800 dark:bg-navy-900 dark:ring-navy-800">
            @forelse ($halaman as $naskah)
                <a href="{{ route('v2.naskah.kerja', $naskah) }}" wire:navigate wire:key="naskah-{{ $naskah->id }}"
                    class="flex items-center gap-4 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-navy-800/60">
                    <span class="w-28 shrink-0 text-xs font-semibold text-slate-500">{{ $naskah->nomor }}</span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-navy-900 dark:text-slate-100">{{ $naskah->tentang }}</span>
                    <span class="hidden shrink-0 text-xs text-slate-400 lg:block">{{ $naskah->updated_at->diffForHumans() }}</span>
                    <x-ui.badge :color="$naskah->tahap->warna()">{{ $naskah->tahap->label() }}</x-ui.badge>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada naskah.</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $halaman->links() }}</div>
    </section>
</div>
