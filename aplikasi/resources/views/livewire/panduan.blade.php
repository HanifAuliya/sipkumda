<div class="mx-auto max-w-4xl">
    <x-ui.page-header title="Panduan" description="Cara memakai SIFKUMDA sesuai peran Anda." icon="book">
        <x-slot:actions>
            {{-- Memuat ulang Dashboard, supaya tur berjalan di layar yang lengkap. --}}
            <x-ui.button variant="outline" icon="signpost-split" :href="route('dashboard', ['tur' => 1])">Ulangi Tur Pengenalan</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($tersedia->count() > 1)
        <nav class="mb-6 flex flex-wrap gap-2" aria-label="Panduan per peran">
            @foreach ($tersedia as $slug => $judul)
                <a href="{{ route('panduan', $slug) }}" wire:navigate @if ($peran === $slug) aria-current="page" @endif
                    @class([
                        'rounded-lg px-4 py-2 text-sm font-semibold transition',
                        'bg-primary-500 text-white shadow-soft' => $peran === $slug,
                        'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-primary-600 dark:bg-navy-900 dark:text-slate-300 dark:ring-navy-700' => $peran !== $slug,
                    ])>{{ $judul }}</a>
            @endforeach
        </nav>
    @endif

    <x-ui.card>
        <article class="isi-panduan">{!! $html !!}</article>
    </x-ui.card>
</div>
