@php
    $kelompok = $halaman->getCollection()->groupBy(fn ($n) => match (true) {
        $n->created_at->isToday() => 'Hari ini',
        $n->created_at->isYesterday() => 'Kemarin',
        default => $n->created_at->translatedFormat('d F Y'),
    });
@endphp

<div class="mx-auto max-w-4xl">
    <x-ui.page-header title="Notifikasi" description="Pekerjaan dan kabar tentang naskah Anda" icon="bell">
        @if ($jumlahBelum)
            <x-slot:actions>
                <x-ui.button variant="outline" icon="check2-all" wire:click="tandaiSemua" loading="tandaiSemua">Tandai semua dibaca</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.tabs>
            <x-ui.tab :active="$filter === 'semua'" icon="list-ul" wire:click="pilihFilter('semua')">Semua</x-ui.tab>
            <x-ui.tab :active="$filter === 'tindakan'" icon="pencil-square" :count="$jumlahTindakan" wire:click="pilihFilter('tindakan')">Perlu Tindakan</x-ui.tab>
            <x-ui.tab :active="$filter === 'belum'" icon="circle-fill" :count="$jumlahBelum" wire:click="pilihFilter('belum')">Belum Dibaca</x-ui.tab>
        </x-ui.tabs>

        <div class="mt-5 space-y-5 transition-opacity duration-150" wire:loading.class="opacity-60">
            @forelse ($kelompok as $hari => $daftar)
                <section wire:key="hari-{{ $hari }}">
                    <h2 class="mb-1.5 px-3 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $hari }}</h2>
                    <div class="space-y-0.5">
                        @foreach ($daftar as $n)
                            <x-naskah.notifikasi :notifikasi="$n" wire:key="notif-{{ $n->id }}" />
                        @endforeach
                    </div>
                </section>
            @empty
                <x-ui.empty-state icon="bell" class="py-12" :title="match ($filter) {
                    'tindakan' => 'Tidak ada yang perlu Anda kerjakan saat ini.',
                    'belum' => 'Semua notifikasi sudah dibaca.',
                    default => 'Belum ada notifikasi.',
                }" />
            @endforelse
        </div>

        <div class="mt-5">{{ $halaman->links() }}</div>
    </x-ui.card>
</div>
